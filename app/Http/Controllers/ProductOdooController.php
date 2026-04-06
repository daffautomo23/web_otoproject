<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OdooApiService;

class ProductOdooController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $page = max(1, (int)$request->get('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        $configs = [
            'PIS' => [
                'url' => 'https://odoo-pis.otoproject.id',
                'db' => 'IOT-Odoo-Otoproject-PIS',
                'username' => 'bod@otoproject.id', // Ganti dengan username Odoo Anda
                'password' => 'OTOPROJECTAUTO', // Ganti dengan password Odoo Anda
            ],
            'MMI' => [
                'url' => 'https://odoo-mmi.otoproject.id',
                'db' => 'IOT-Odoo-Otoproject-MMI',
                'username' => 'bod@otoproject.id', // Ganti dengan username Odoo Anda
                'password' => 'OTOPROJECTAUTO', // Ganti dengan password Odoo Anda
            ],
        ];
        $products = [];
        $hasNext = [];
        foreach ($configs as $key => $config) {
            $odoo = new OdooApiService($config);
            if ($search) {
                $domain = ['|', '|',
                    ['name', 'ilike', $search],
                    ['default_code', 'ilike', $search],
                    ['id', '=', (int)$search]
                ];
                $products[$key] = $odoo->getProducts(['name', 'default_code', 'list_price', 'id'], 0, 500, $domain); // ambil max 500 hasil
                $hasNext[$key] = false;
            } else {
                $products[$key] = $odoo->getProducts(['name', 'default_code', 'list_price', 'id'], $offset, $perPage);
                // Cek apakah ada produk berikutnya
                $hasNext[$key] = count($odoo->getProducts(['id'], $offset + $perPage, 1)) > 0;
            }
        }
        return view('products.odoo_products', compact('products', 'page', 'perPage', 'hasNext', 'search'));
    }

    public function show($db, $id)
    {
        $configs = [
            'PIS' => [
                'url' => 'https://odoo-pis.otoproject.id',
                'db' => 'IOT-Odoo-Otoproject-PIS',
                'username' => 'bod@otoproject.id',
                'password' => 'OTOPROJECTAUTO',
            ],
            'MMI' => [
                'url' => 'https://odoo-mmi.otoproject.id',
                'db' => 'IOT-Odoo-Otoproject-MMI',
                'username' => 'bod@otoproject.id',
                'password' => 'OTOPROJECTAUTO',
            ],
        ];
        if (!isset($configs[$db])) abort(404);
        $odoo = new OdooApiService($configs[$db]);
        $product = $odoo->getProducts(['name', 'default_code', 'list_price', 'id'], 0, 1, [['id', '=', (int)$id]]);
        $detail = $product[0] ?? null;
        return view('products.odoo_product_detail', compact('detail', 'db'));
    }
}
