<?php

namespace App\Http\Controllers;

class ProfileController extends Controller
{
    public function show()
    {
        $user = $this->authUser();

        $stats = [];

        if ($user->role === 'customer') {
            $stats = [
                'total' => (int) db_value('SELECT COUNT(*) FROM service_requests WHERE customer_id = ?', [$user->id]),
                'active' => (int) db_value("SELECT COUNT(*) FROM service_requests WHERE customer_id = ? AND status IN ('pending','assigned','accepted','in_progress')", [$user->id]),
                'completed' => (int) db_value("SELECT COUNT(*) FROM service_requests WHERE customer_id = ? AND status = 'completed'", [$user->id]),
            ];
        } elseif ($user->role === 'technician') {
            $stats = [
                'active' => (int) db_value("SELECT COUNT(*) FROM service_requests WHERE technician_id = ? AND status IN ('assigned','accepted','in_progress')", [$user->id]),
                'completed' => (int) db_value("SELECT COUNT(*) FROM service_requests WHERE technician_id = ? AND status = 'completed'", [$user->id]),
                'rating' => round((float) db_value('SELECT COALESCE(AVG(rating), 0) FROM feedback WHERE technician_id = ?', [$user->id]), 1),
            ];
        }

        return view('profile.index', [
            'title' => 'My profile',
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    public function update()
    {
        $user = $this->authUser();
        $id = (int) $user->id;

        $data = [
            'name' => $this->input('name'),
            'phone' => $this->input('phone'),
            'address' => $this->input('address'),
            'specialty' => $this->input('specialty'),
            'bio' => $this->input('bio'),
        ];

        $errors = [];

        if (mb_strlen($data['name']) < 3) {
            $errors['name'] = 'Please enter your full name.';
        }

        if ($errors) {
            return $this->backWithErrors($errors, '/profile');
        }

        db_exec(
            'UPDATE users SET name = ?, phone = ?, address = ?, specialty = ?, bio = ?, updated_at = NOW() WHERE id = ?',
            [
                $data['name'],
                $data['phone'] ?: null,
                $data['address'] ?: null,
                $user->role === 'technician' ? ($data['specialty'] ?: null) : ($user->specialty ?? null),
                $user->role === 'technician' ? ($data['bio'] ?: null) : ($user->bio ?? null),
                $id,
            ]
        );

        $this->log('profile_update', 'Updated profile details');
        $this->flash('success', 'Profile updated successfully.');

        return redirect('/profile');
    }

    public function password()
    {
        $user = $this->authUser();
        $id = (int) $user->id;

        $current = $this->input('current_password');
        $password = $this->input('new_password');
        $confirmation = $this->input('new_password_confirmation');

        $errors = [];

        $record = db_one('SELECT password_hash FROM users WHERE id = ?', [$id]);

        if (! $record || ! password_verify($current, $record['password_hash'])) {
            $errors['current_password'] = 'Your current password is incorrect.';
        }

        if (mb_strlen($password) < 8) {
            $errors['new_password'] = 'Use at least 8 characters.';
        } elseif ($password !== $confirmation) {
            $errors['new_password_confirmation'] = 'Passwords do not match.';
        }

        if ($errors) {
            return $this->backWithErrors($errors, '/profile');
        }

        db_exec(
            'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $id]
        );

        $this->log('password_change', 'Changed account password');
        $this->flash('success', 'Password updated. Use your new password next time you sign in.');

        return redirect('/profile');
    }
}
