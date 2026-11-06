<?php

$router->get('/', 'HomeController@index');
$router->get('/services', 'HomeController@services');
$router->get('/services/{slug}', 'HomeController@serviceShow');
$router->get('/about', 'HomeController@about');
$router->get('/contact', 'HomeController@contact');
$router->post('/contact', 'HomeController@contactStore');
$router->get('/p/{slug}', 'HomeController@page');

$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/logout', 'AuthController@logout');

$router->get('/profile', 'ProfileController@show', ['admin', 'technician', 'customer']);
$router->post('/profile', 'ProfileController@update', ['admin', 'technician', 'customer']);
$router->post('/profile/password', 'ProfileController@password', ['admin', 'technician', 'customer']);

$router->get('/customer', 'CustomerController@dashboard', ['customer']);
$router->get('/customer/requests', 'CustomerController@index', ['customer']);
$router->get('/customer/requests/new', 'CustomerController@create', ['customer']);
$router->post('/customer/requests', 'CustomerController@store', ['customer']);
$router->get('/customer/requests/{id}', 'CustomerController@show', ['customer']);
$router->post('/customer/requests/{id}/cancel', 'CustomerController@cancel', ['customer']);
$router->post('/customer/requests/{id}/feedback', 'CustomerController@feedback', ['customer']);

$router->get('/technician', 'TechnicianController@dashboard', ['technician']);
$router->get('/technician/jobs', 'TechnicianController@jobs', ['technician']);
$router->get('/technician/jobs/{id}', 'TechnicianController@show', ['technician']);
$router->post('/technician/jobs/{id}/accept', 'TechnicianController@accept', ['technician']);
$router->post('/technician/jobs/{id}/reject', 'TechnicianController@reject', ['technician']);
$router->post('/technician/jobs/{id}/start', 'TechnicianController@start', ['technician']);
$router->post('/technician/jobs/{id}/complete', 'TechnicianController@complete', ['technician']);
$router->get('/technician/schedule', 'TechnicianController@schedule', ['technician']);

$router->get('/admin', 'AdminController@dashboard', ['admin']);
$router->get('/admin/requests', 'AdminController@requests', ['admin']);
$router->get('/admin/requests/{id}', 'AdminController@requestShow', ['admin']);
$router->post('/admin/requests/{id}/assign', 'AdminController@assign', ['admin']);
$router->post('/admin/requests/{id}/status', 'AdminController@updateStatus', ['admin']);
$router->post('/admin/requests/{id}/schedule', 'AdminController@schedule', ['admin']);
$router->post('/admin/requests/{id}/cost', 'AdminController@updateCost', ['admin']);
$router->get('/admin/appointments', 'AdminController@appointments', ['admin']);
$router->post('/admin/appointments/{id}/status', 'AdminController@appointmentStatus', ['admin']);
$router->get('/admin/feedback', 'AdminController@feedback', ['admin']);
$router->get('/admin/messages', 'AdminController@messages', ['admin']);
$router->post('/admin/messages/{id}/status', 'AdminController@messageStatus', ['admin']);
$router->get('/admin/reports', 'AdminController@reports', ['admin']);
$router->get('/admin/reports/export', 'AdminController@export', ['admin']);
$router->get('/admin/activity', 'AdminController@activity', ['admin']);
$router->get('/admin/settings', 'AdminController@settings', ['admin']);
$router->post('/admin/settings', 'AdminController@saveSettings', ['admin']);

$router->get('/admin/technicians', 'AdminPeopleController@technicians', ['admin']);
$router->get('/admin/technicians/create', 'AdminPeopleController@technicianCreate', ['admin']);
$router->post('/admin/technicians', 'AdminPeopleController@technicianStore', ['admin']);
$router->get('/admin/technicians/{id}/edit', 'AdminPeopleController@technicianEdit', ['admin']);
$router->post('/admin/technicians/{id}', 'AdminPeopleController@technicianUpdate', ['admin']);
$router->post('/admin/technicians/{id}/delete', 'AdminPeopleController@technicianDelete', ['admin']);
$router->get('/admin/customers', 'AdminPeopleController@customers', ['admin']);
$router->get('/admin/customers/{id}', 'AdminPeopleController@customerShow', ['admin']);

$router->get('/admin/services', 'AdminContentController@services', ['admin']);
$router->get('/admin/services/create', 'AdminContentController@serviceCreate', ['admin']);
$router->post('/admin/services', 'AdminContentController@serviceStore', ['admin']);
$router->get('/admin/services/{id}/edit', 'AdminContentController@serviceEdit', ['admin']);
$router->post('/admin/services/{id}', 'AdminContentController@serviceUpdate', ['admin']);
$router->post('/admin/services/{id}/delete', 'AdminContentController@serviceDelete', ['admin']);
$router->get('/admin/pages', 'AdminContentController@pages', ['admin']);
$router->get('/admin/pages/create', 'AdminContentController@pageCreate', ['admin']);
$router->post('/admin/pages', 'AdminContentController@pageStore', ['admin']);
$router->get('/admin/pages/{id}/edit', 'AdminContentController@pageEdit', ['admin']);
$router->post('/admin/pages/{id}', 'AdminContentController@pageUpdate', ['admin']);
$router->post('/admin/pages/{id}/delete', 'AdminContentController@pageDelete', ['admin']);
