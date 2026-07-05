<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class PatientController extends Controller
{
    /**
     * List all patients across all tenants (God Mode)
     */
    public function index(Request $request)
    {
        try {
            $allPatients = [];
            $tenants = Tenant::all();

            foreach ($tenants as $tenant) {
                try {
                    $this->switchToTenant($tenant);
                    
                    $patients = Patient::all();
                    
                    foreach ($patients as $patient) {
                        $patientArray = $patient->toArray();
                        $patientArray['tenant_name'] = $tenant->name;
                        $patientArray['tenant_domain'] = $tenant->domain;
                        $allPatients[] = $patientArray;
                    }
                } catch (\Exception $e) {
                    Log::error('Error fetching patients from tenant: ' . $tenant->name);
                }
            }

            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $allPatients = array_filter($allPatients, function($patient) use ($search) {
                    return stripos($patient['first_name'], $search) !== false || 
                           stripos($patient['last_name'], $search) !== false ||
                           stripos($patient['email'], $search) !== false ||
                           stripos($patient['medical_record_number'], $search) !== false;
                });
            }

            usort($allPatients, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
            $total = count($allPatients);
            $offset = ($page - 1) * $perPage;
            $patientData = array_slice($allPatients, $offset, $perPage);

            return response()->json([
                'patients' => [
                    'data' => $patientData,
                    'total' => $total,
                    'per_page' => (int)$perPage,
                    'current_page' => (int)$page,
                    'last_page' => ceil($total / $perPage),
                ],
                'total_tenants' => $tenants->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching all patients: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch patients'], 500);
        }
    }

    /**
     * Get a single patient from any tenant (God Mode)
     */
    public function show($id)
    {
        try {
            $tenants = Tenant::all();
            
            foreach ($tenants as $tenant) {
                try {
                    $this->switchToTenant($tenant);
                    
                    $patient = Patient::where('id', $id)->first();
                    if ($patient) {
                        $patientArray = $patient->toArray();
                        $patientArray['tenant_name'] = $tenant->name;
                        $patientArray['tenant_domain'] = $tenant->domain;
                        
                        Config::set('database.default', 'landlord');
                        DB::connection('landlord')->reconnect();
                        
                        return response()->json($patientArray);
                    }
                } catch (\Exception $e) {
                    Log::error('Error searching tenant: ' . $tenant->name);
                }
            }

            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json(['message' => 'Patient not found'], 404);

        } catch (\Exception $e) {
            Log::error('Error finding patient: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to find patient'], 500);
        }
    }

    /**
     * Delete a patient from any tenant (God Mode)
     */
    public function destroy($id)
    {
        try {
            $tenant = null;
            $patient = null;

            $tenants = Tenant::all();
            foreach ($tenants as $t) {
                try {
                    $this->switchToTenant($t);
                    
                    $found = Patient::where('id', $id)->first();
                    if ($found) {
                        $patient = $found;
                        $tenant = $t;
                        break;
                    }
                } catch (\Exception $e) {
                    Log::error('Error searching tenant: ' . $t->name);
                }
            }

            if (!$patient) {
                Config::set('database.default', 'landlord');
                DB::connection('landlord')->reconnect();
                return response()->json(['message' => 'Patient not found'], 404);
            }

            $patient->delete();

            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json([
                'message' => 'Patient deleted successfully',
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting patient: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete patient'], 500);
        }
    }

    /**
     * Switch to tenant database
     */
    private function switchToTenant(Tenant $tenant): void
    {
        $host = Config::get('database.connections.landlord.host', env('DB_HOST', '127.0.0.1'));
        $port = Config::get('database.connections.landlord.port', env('DB_PORT', '3306'));
        $username = Config::get('database.connections.landlord.username', env('DB_USERNAME', 'root'));
        $password = Config::get('database.connections.landlord.password', env('DB_PASSWORD', ''));

        Config::set('database.connections.tenant', [
            'driver' => 'mysql',
            'host' => $host,
            'port' => $port,
            'database' => $tenant->database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        DB::purge('tenant');
        DB::connection('tenant')->reconnect();
        Config::set('database.default', 'tenant');
        $tenant->makeCurrent();
    }
}