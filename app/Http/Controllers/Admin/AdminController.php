<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

abstract class AdminController extends Controller
{
    /**
     * Redirect ke route dengan flash success.
     */
    protected function success(
        string $route,
        string $message
    ): RedirectResponse {

        return redirect()

            ->route($route)

            ->with('success', $message);
    }

    /**
     * Redirect kembali dengan flash error.
     */
    protected function error(
        string $message
    ): RedirectResponse {

        return back()

            ->withInput()

            ->with('error', $message);
    }

    /**
     * Redirect kembali dengan warning.
     */
    protected function warning(
        string $message
    ): RedirectResponse {

        return back()

            ->withInput()

            ->with('warning', $message);
    }

    /**
     * Redirect kembali dengan info.
     */
    protected function info(
        string $message
    ): RedirectResponse {

        return back()

            ->with('info', $message);
    }

    /**
     * Redirect kembali dengan success.
     */
    protected function successBack(
        string $message
    ): RedirectResponse {

        return back()

            ->with('success', $message);
    }

    protected function allow(
        string $ability,
        mixed $model
    ): void {

        $this->authorize(

            $ability,

            $model

        );

    }

    protected function notFound(
        string $message = 'Data tidak ditemukan.'
    ){
        abort(404,$message);
    }

    protected function jsonSuccess(

        string $message,

        mixed $data = null

    ){

        return response()->json([

            'success'=>true,

            'message'=>$message,

            'data'=>$data

        ]);

    }
}