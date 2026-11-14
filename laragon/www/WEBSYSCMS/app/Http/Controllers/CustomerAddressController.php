<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerAddressController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $customer = $this->authUser();
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        DB::table('customer_addresses')->insert([
            'customer_id' => $customer->id,
            'label' => $data['label'],
            'address' => $data['address'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'accuracy' => $data['accuracy'] ?? null,
            'created_at' => DB::raw('CURRENT_TIMESTAMP'),
            'updated_at' => DB::raw('CURRENT_TIMESTAMP'),
        ]);

        $this->flash('success', 'Address saved to your address book.');

        return redirect('/profile#saved-addresses');
    }

    public function destroy(int $id): RedirectResponse
    {
        $customer = $this->authUser();
        $address = DB::table('customer_addresses')
            ->where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();

        abort_if(! $address, 404);

        DB::table('customer_addresses')->where('id', $id)->delete();
        $this->flash('success', 'Saved address deleted.');

        return redirect('/profile#saved-addresses');
    }
}
