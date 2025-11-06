<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    /**
     * Display a listing of companies.
     */
    public function index(Request $request)
    {
        $query = Company::query();

        // Filter by tax_id
        if ($request->has('tax_id')) {
            $query->where('tax_id', $request->tax_id);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->get('per_page', 50);
        $companies = $query->orderBy('name')->paginate($perPage);

        return response()->json($companies);
    }

    /**
     * Store a newly created company.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'tax_id' => 'required|string|unique:companies,tax_id',
            'vat_number' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $company = Company::create($request->all());

        return response()->json([
            'message' => 'Company created successfully',
            'data' => $company
        ], 201);
    }

    /**
     * Display the specified company.
     */
    public function show($id)
    {
        $company = Company::findOrFail($id);
        return response()->json($company);
    }

    /**
     * Update the specified company.
     */
    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'tax_id' => 'sometimes|string|unique:companies,tax_id,' . $id,
            'vat_number' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $company->update($request->all());

        return response()->json([
            'message' => 'Company updated successfully',
            'data' => $company
        ]);
    }

    /**
     * Remove the specified company.
     */
    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->json([
            'message' => 'Company deleted successfully'
        ]);
    }

    /**
     * Get or create a company by tax_id.
     */
    public function getOrCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'tax_id' => 'required|string',
            'vat_number' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $company = Company::firstOrCreate(
            ['tax_id' => $request->tax_id],
            $request->only(['name', 'vat_number', 'address', 'phone', 'email'])
        );

        return response()->json([
            'message' => $company->wasRecentlyCreated ? 'Company created' : 'Company found',
            'data' => $company,
            'created' => $company->wasRecentlyCreated
        ], $company->wasRecentlyCreated ? 201 : 200);
    }
}