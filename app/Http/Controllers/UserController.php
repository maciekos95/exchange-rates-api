<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Create a new UserController instance.
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
        $this->middleware('role_or_permission:admin|create-users', ['only' => ['create']]);
        $this->middleware('role_or_permission:admin|edit-users', ['only' => ['edit']]);
        $this->middleware('role_or_permission:admin|delete-users', ['only' => ['delete']]);
    }

    /**
     * Login user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        $token = Auth::attempt($credentials);

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - incorrect credentials',
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged in.',
            'user' => Auth::user(),
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    /**
     * Logout current user.
     *
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
        Auth::logout();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out.',
            'user' => Auth::user(),
        ]);
    }

    /**
     * Refresh token for current user.
     *
     * @return \Illuminate\Http\Response
     */
    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully refreshed token.',
            'user' => Auth::user(),
            'authorisation' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    /**
     * Change password for current user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully changed password.',
            'user' => $user,
        ]);
    }

    /**
     * Create a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required', Rule::in(array_map('strtolower', ['admin', 'user', 'editor'])),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->assignRole($request->role);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully created user.',
            'user' => $user,
        ], 201);
    }

    /**
     * Edit an existing user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $validator = Validator::make(['id' => $id], [
            'id' => 'integer|exists:users,id'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('id', $id)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Requested user not found in the database.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'string|email|max:255|unique:users',
            'password' => 'string|min:8',
            'role' => Rule::in(array_map('strtolower', ['admin', 'user', 'editor'])),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->filled('name')) {
            $user->name = $request->name;
        }
        
        if ($request->filled('email')) {
            $user->email = $request->email;
        }
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        
        if ($request->filled('role')) {
            $user->syncRoles([$request->role]);
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully edited user.',
            'user' => $user,
        ]);
    }

    /**
     * Delete existing user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $validator = Validator::make(['id' => $id], [
            'id' => 'integer|exists:users,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('id', $id)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Requested user not found in the database.',
            ], 404);
        }

        if ($tokens = $user->tokens) {
            foreach ($tokens as $token) {
                $token->delete();
            }
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully deleted user.',
            'user' => $user,
        ]);
    }
}
