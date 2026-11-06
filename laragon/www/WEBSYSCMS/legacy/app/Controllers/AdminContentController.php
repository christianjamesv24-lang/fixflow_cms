<?php

class AdminContentController extends Controller
{
    private array $iconChoices = ['snowflake', 'fridge', 'washer', 'oven', 'microwave', 'droplet', 'activity', 'refresh', 'wrench', 'zap', 'shield', 'settings', 'tool'];

    public function services(): void
    {
        $categories = Database::all(
            'SELECT c.*, (SELECT COUNT(*) FROM service_requests r WHERE r.category_id = c.id) AS request_count
             FROM service_categories c ORDER BY c.name'
        );

        $this->view('admin/services', [
            'title' => 'Service catalog',
            'categories' => $categories,
        ], 'app');
    }

    public function serviceCreate(): void
    {
        $this->view('admin/service-form', [
            'title' => 'Add service category',
            'category' => null,
            'iconChoices' => $this->iconChoices,
        ], 'app');
    }

    public function serviceStore(): void
    {
        $data = $this->serviceData();
        $errors = $this->validateService($data, null);

        if ($errors) {
            $this->backWithErrors($errors, '/admin/services/create');
        }

        Database::insert(
            'INSERT INTO service_categories (name, slug, icon, short_description, description, base_price, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            [$data['name'], $data['slug'], $data['icon'], $data['short_description'] ?: null, $data['description'] ?: null, $data['base_price'], $data['status']]
        );

        $this->log('create_service', 'Added service category '.$data['name']);
        $this->flash('success', 'Service category added.');
        redirect('/admin/services');
    }

    public function serviceEdit(array $params): void
    {
        $category = $this->findCategory((int) ($params['id'] ?? 0));

        $this->view('admin/service-form', [
            'title' => 'Edit service category',
            'category' => $category,
            'iconChoices' => $this->iconChoices,
        ], 'app');
    }

    public function serviceUpdate(array $params): void
    {
        $category = $this->findCategory((int) ($params['id'] ?? 0));
        $data = $this->serviceData();
        $errors = $this->validateService($data, (int) $category['id']);

        if ($errors) {
            $this->backWithErrors($errors, '/admin/services/'.$category['id'].'/edit');
        }

        Database::exec(
            'UPDATE service_categories SET name = ?, slug = ?, icon = ?, short_description = ?, description = ?, base_price = ?, status = ?, updated_at = NOW() WHERE id = ?',
            [
                $data['name'],
                $data['slug'],
                $data['icon'],
                $data['short_description'] ?: null,
                $data['description'] ?: null,
                $data['base_price'],
                $data['status'],
                $category['id'],
            ]
        );

        $this->log('update_service', 'Updated service category '.$data['name']);
        $this->flash('success', 'Service category updated.');
        redirect('/admin/services');
    }

    public function serviceDelete(array $params): void
    {
        $category = $this->findCategory((int) ($params['id'] ?? 0));

        $requestCount = (int) Database::value('SELECT COUNT(*) FROM service_requests WHERE category_id = ?', [$category['id']]);

        if ($requestCount > 0) {
            Database::exec("UPDATE service_categories SET status = 'inactive', updated_at = NOW() WHERE id = ?", [$category['id']]);
            $this->flash('info', $category['name'].' has '.$requestCount.' linked request(s), so it was deactivated instead of deleted.');
        } else {
            Database::exec('DELETE FROM service_categories WHERE id = ?', [$category['id']]);
            $this->log('delete_service', 'Deleted service category '.$category['name']);
            $this->flash('success', $category['name'].' was deleted.');
        }

        redirect('/admin/services');
    }

    public function pages(): void
    {
        $pages = Database::all('SELECT * FROM cms_pages ORDER BY updated_at DESC');

        $this->view('admin/pages', [
            'title' => 'CMS pages',
            'pages' => $pages,
        ], 'app');
    }

    public function pageCreate(): void
    {
        $this->view('admin/page-form', [
            'title' => 'New page',
            'page' => null,
        ], 'app');
    }

    public function pageStore(): void
    {
        $data = $this->pageData();
        $errors = $this->validatePage($data, null);

        if ($errors) {
            $this->backWithErrors($errors, '/admin/pages/create');
        }

        Database::insert(
            'INSERT INTO cms_pages (title, slug, excerpt, content, meta_title, meta_description, status, show_in_footer, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $data['title'],
                $data['slug'],
                $data['excerpt'] ?: null,
                $data['content'],
                $data['meta_title'] ?: null,
                $data['meta_description'] ?: null,
                $data['status'],
                $data['show_in_footer'],
            ]
        );

        $this->log('create_page', 'Created CMS page '.$data['title']);
        $this->flash('success', 'Page created.');
        redirect('/admin/pages');
    }

    public function pageEdit(array $params): void
    {
        $page = $this->findPage((int) ($params['id'] ?? 0));

        $this->view('admin/page-form', [
            'title' => 'Edit page',
            'page' => $page,
        ], 'app');
    }

    public function pageUpdate(array $params): void
    {
        $page = $this->findPage((int) ($params['id'] ?? 0));
        $data = $this->pageData();
        $errors = $this->validatePage($data, (int) $page['id']);

        if ($errors) {
            $this->backWithErrors($errors, '/admin/pages/'.$page['id'].'/edit');
        }

        Database::exec(
            'UPDATE cms_pages SET title = ?, slug = ?, excerpt = ?, content = ?, meta_title = ?, meta_description = ?, status = ?, show_in_footer = ?, updated_at = NOW() WHERE id = ?',
            [
                $data['title'],
                $data['slug'],
                $data['excerpt'] ?: null,
                $data['content'],
                $data['meta_title'] ?: null,
                $data['meta_description'] ?: null,
                $data['status'],
                $data['show_in_footer'],
                $page['id'],
            ]
        );

        $this->log('update_page', 'Updated CMS page '.$data['title']);
        $this->flash('success', 'Page updated.');
        redirect('/admin/pages');
    }

    public function pageDelete(array $params): void
    {
        $page = $this->findPage((int) ($params['id'] ?? 0));

        if (in_array($page['slug'], ['about', 'terms', 'privacy'], true)) {
            $this->flash('error', 'Core pages (about, terms, privacy) cannot be deleted.');
            back('/admin/pages');
        }

        Database::exec('DELETE FROM cms_pages WHERE id = ?', [$page['id']]);
        $this->log('delete_page', 'Deleted CMS page '.$page['title']);
        $this->flash('success', 'Page deleted.');
        redirect('/admin/pages');
    }

    private function findCategory(int $id): array
    {
        $category = Database::one('SELECT * FROM service_categories WHERE id = ?', [$id]);

        if (! $category) {
            $this->notFound('That service category could not be found.');
        }

        return $category;
    }

    private function serviceData(): array
    {
        $name = $this->input('name');
        $slug = slugify($this->input('slug') !== '' ? $this->input('slug') : $name);
        $icon = $this->input('icon');

        return [
            'name' => $name,
            'slug' => $slug,
            'icon' => in_array($icon, $this->iconChoices, true) ? $icon : 'wrench',
            'short_description' => $this->input('short_description'),
            'description' => $this->input('description'),
            'base_price' => $this->input('base_price', '0'),
            'status' => in_array($this->input('status'), ['active', 'inactive'], true) ? $this->input('status') : 'active',
        ];
    }

    private function validateService(array $data, ?int $ignoreId): array
    {
        $errors = [];

        if (mb_strlen($data['name']) < 3) {
            $errors['name'] = 'Enter a service name.';
        }

        if ($data['slug'] === '') {
            $errors['slug'] = 'A URL slug is required.';
        } else {
            $existing = Database::one('SELECT id FROM service_categories WHERE slug = ?', [$data['slug']]);
            if ($existing && (int) $existing['id'] !== (int) $ignoreId) {
                $errors['slug'] = 'That slug is already used by another category.';
            }
        }

        if (! is_numeric($data['base_price']) || (float) $data['base_price'] < 0) {
            $errors['base_price'] = 'Enter a valid base price.';
        }

        return $errors;
    }

    private function findPage(int $id): array
    {
        $page = Database::one('SELECT * FROM cms_pages WHERE id = ?', [$id]);

        if (! $page) {
            $this->notFound('That page could not be found.');
        }

        return $page;
    }

    private function pageData(): array
    {
        $title = $this->input('title');
        $slug = slugify($this->input('slug') !== '' ? $this->input('slug') : $title);

        return [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $this->input('excerpt'),
            'content' => $this->input('content'),
            'meta_title' => $this->input('meta_title'),
            'meta_description' => $this->input('meta_description'),
            'status' => in_array($this->input('status'), ['published', 'draft'], true) ? $this->input('status') : 'draft',
            'show_in_footer' => isset($_POST['show_in_footer']) ? 1 : 0,
        ];
    }

    private function validatePage(array $data, ?int $ignoreId): array
    {
        $errors = [];

        if (mb_strlen($data['title']) < 2) {
            $errors['title'] = 'Enter a page title.';
        }

        if ($data['slug'] === '') {
            $errors['slug'] = 'A URL slug is required.';
        } else {
            $existing = Database::one('SELECT id FROM cms_pages WHERE slug = ?', [$data['slug']]);
            if ($existing && (int) $existing['id'] !== (int) $ignoreId) {
                $errors['slug'] = 'That slug is already in use.';
            }
        }

        if ($data['content'] === '') {
            $errors['content'] = 'Add some page content.';
        }

        return $errors;
    }
}
