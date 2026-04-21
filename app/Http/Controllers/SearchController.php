<?php 


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Typesense\Client;

class SearchController extends Controller
{
    public function semantic(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return response()->json(['hits' => []]);
        }

        $client = app(Client::class);

        $results = $client->collections['project_manuscripts']
            ->documents
            ->search([
                'q' => $query,

                // ✅ keyword
                'query_by' => 'title,abstract',

                // 🔥 semantic
                'vector_query' => 'embedding:([], k:10)',
            ]);

        return response()->json($results);
    }
}