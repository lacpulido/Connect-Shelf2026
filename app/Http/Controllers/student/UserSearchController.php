<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = trim((string) $request->input('q', ''));
        $userType = (int) $request->input('user_type', 2);

        if ($query === '') {
            return response()->json([]);
        }

        $users = User::query()
            ->where('id', '!=', Auth::id())
            ->when($userType === 1, function ($q) {
                // ADVISER / FACULTY SEARCH
                $q->where('user_type', 1)
                    ->whereDoesntHave('roles', function ($roleQuery) {
                        $roleQuery->where('name', 'Administrator');
                    });
            }, function ($q) {
                // DEFAULT SEARCH = RESEARCHERS (STUDENTS ONLY)
                $q->where('user_type', 2);
            })
            ->where(function ($qBuilder) use ($query) {
                $qBuilder->where('first_name', 'LIKE', "%{$query}%")
                    ->orWhere('last_name', 'LIKE', "%{$query}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"])
                    ->orWhere('email', 'LIKE', "%{$query}%");
            })
            ->select('id', 'first_name', 'last_name', 'email', 'user_type')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(5)
            ->get();

        return response()->json($users);
    }
}