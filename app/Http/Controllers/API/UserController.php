<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use App\Http\Requests\UserRequest;
use App\Models\User;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response(User::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        try{
            $data = $request->validated();
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            return response( $user );
        } catch (\Exception $e) {
            return response(['error' => sprintf("Error while creating user. Error: %s", $e->getMessage())]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return response($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, User $user)
    {
        try{
            $data = $request->validated();
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            return response( $user );
        } catch (\Exception $e) {
            return response(['error' => sprintf("Error while updating user. Error: %s", $e->getMessage())]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete(User $user)
    {
        try{
            $user->delete();
            return response( $user );
        } catch (\Exception $e) {
            return response(['error' => sprintf("Error while deleting user. Error: %s", $e->getMessage())]);
        }
    }
}
