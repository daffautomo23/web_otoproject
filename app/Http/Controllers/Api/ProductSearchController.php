<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OdooApiService;
use Illuminate\Support\Facades\Log;

class ProductSearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            $search = $request->get('search');
            $sku = $request->get('sku');

            // Jika ada parameter sku, gunakan untuk pencarian
            if ($sku && strlen($sku) >= 2) {
                $searchValue = $sku;
                $domain = [['default_code', 'ilike', $searchValue]];
            } elseif ($search && strlen($search) >= 2) {
                $searchValue = $search;
                $domain = ['|', '|',
                    ['name', 'ilike', $searchValue],
                    ['default_code', 'ilike', $searchValue],
                    ['id', '=', is_numeric($searchValue) ? (int)$searchValue : 0]
                ];
            } else {
                return response()->json([]);
            }

            $configs = [
                'PIS' => [
                    'url' => 'https://odoo-pis.otoproject.id',
                    'db' => 'IOT-Odoo-Otoproject-PIS',
                    'username' => 'bod@otoproject.id',
                    'password' => 'otoprojectauto',
                ],
                'MMI' => [
                    'url' => 'https://odoo-mmi.otoproject.id',
                    'db' => 'IOT-Odoo-Otoproject-MMI',
                    'username' => 'bod@otoproject.id',
                    'password' => 'otoprojectauto',
                ],
            ];

            $allProducts = [];

            foreach ($configs as $key => $config) {
                try {
                    $odoo = new OdooApiService($config);
                    $products = $odoo->getProducts(['name', 'default_code', 'list_price', 'id'], 0, 20, $domain);

                    foreach ($products as $product) {
                        $product['database'] = $key;
                        $allProducts[] = $product;
                    }
                } catch (\Exception $e) {
                    Log::error("Error searching products in {$key}: " . $e->getMessage());
                }
            }

            // Sort by relevance
            if (isset($searchValue)) {
                usort($allProducts, function($a, $b) use ($searchValue) {
                    $searchLower = strtolower($searchValue);
                    $aNameLower = strtolower($a['name']);
                    $bNameLower = strtolower($b['name']);
                    return strcmp($aNameLower, $bNameLower);
                });
            }

            $allProducts = array_slice($allProducts, 0, 50);

            return response()->json($allProducts);

        } catch (\Exception $e) {
            Log::error('Product search error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to search products'], 500);
        }
    }
}