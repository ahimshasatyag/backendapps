<?php

namespace App\Http\Controllers\Customerscontact;

use App\Http\Controllers\Controller;
use App\Models\Customerscontact\CustomerContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CustomerContactController extends Controller
{
    public function index(Request $request)
    {
        $contacts = CustomerContact::all();

        return response()->json([
            'status' => 'success',
            'data' => $contacts
        ]);
    }

    public function show($id)
    {
        $contact = CustomerContact::find($id);

        if (!$contact) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer Contact not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $contact
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_customers' => 'required|integer',
            'nm_customers_contact' => 'required|string',
            'customers_contact_posisi' => 'nullable|string',
            'customers_contact_phone' => 'nullable|string',
            'customers_contact_mobile' => 'nullable|string',
            'customers_contact_email' => 'nullable|email',
            'customers_contact_address' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $contact = CustomerContact::create([
            'id_customers' => $request->id_customers,
            'nm_customers_contact' => $request->nm_customers_contact,
            'customers_contact_posisi' => $request->customers_contact_posisi,
            'customers_contact_phone' => $request->customers_contact_phone,
            'customers_contact_mobile' => $request->customers_contact_mobile,
            'customers_contact_email' => $request->customers_contact_email,
            'customers_contact_address' => $request->customers_contact_address
        ]);

        Log::info('Simpan Data Customer Contact ID : ' . $contact->id_customers_contact);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer Contact created successfully',
            'data' => $contact
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $contact = CustomerContact::find($id);

        if (!$contact) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer Contact not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_customers' => 'required|integer',
            'nm_customers_contact' => 'required|string',
            'customers_contact_posisi' => 'nullable|string',
            'customers_contact_phone' => 'nullable|string',
            'customers_contact_mobile' => 'nullable|string',
            'customers_contact_email' => 'nullable|email',
            'customers_contact_address' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $contact->update([
            'id_customers' => $request->id_customers,
            'nm_customers_contact' => $request->nm_customers_contact,
            'customers_contact_posisi' => $request->customers_contact_posisi,
            'customers_contact_phone' => $request->customers_contact_phone,
            'customers_contact_mobile' => $request->customers_contact_mobile,
            'customers_contact_email' => $request->customers_contact_email,
            'customers_contact_address' => $request->customers_contact_address
        ]);

        Log::info('Update Data Customer Contact ID : ' . $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer Contact updated successfully',
            'data' => $contact
        ]);
    }

    public function destroy($id)
    {
        $contact = CustomerContact::find($id);

        if (!$contact) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer Contact not found'
            ], 404);
        }

        $contact->delete();

        Log::info('Hapus Data Customer Contact ID : ' . $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer Contact deleted successfully'
        ]);
    }
}
