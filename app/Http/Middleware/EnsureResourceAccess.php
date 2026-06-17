<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureResourceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();

            if (!empty($admin->planta)) {
                $indicador = $request->route('indicador');
                $departamento = $request->route('departamento');
                $norma = $request->route('norma');

                if ($indicador && is_object($indicador)) {
                    $planta = $indicador->planta ?? null;
                    if (!empty($planta) && $planta !== $admin->planta) {
                        abort(403, 'No tienes acceso a este indicador.');
                    }
                }

                if ($departamento && is_object($departamento)) {
                    $planta = $departamento->planta ?? null;
                    if (!empty($planta) && $planta !== $admin->planta) {
                        abort(403, 'No tienes acceso a este departamento.');
                    }
                }

                if ($norma && is_object($norma)) {
                    $planta = $norma->planta ?? null;
                    if (!empty($planta) && $planta !== $admin->planta) {
                        abort(403, 'No tienes acceso a esta norma.');
                    }
                }
            }

            return $next($request);
        }

        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            $userDeptId = $user->id_departamento;

            if (!$userDeptId) {
                return $next($request);
            }

            $indicador = $request->route('indicador');
            $norma = $request->route('norma');
            $encuesta = $request->route('encuesta');
            $departamento = $request->route('departamento');
            $apartado = $request->route('apartado');

            if ($indicador && is_object($indicador) && method_exists($indicador, 'departamento')) {
                $puedeAcceder = $indicador->departamento->id === $userDeptId;

                if (!$puedeAcceder && method_exists($indicador, 'departamentosForaneos')) {
                    $puedeAcceder = $indicador->departamentosForaneos()
                        ->where('departamentos.id', $userDeptId)
                        ->exists();
                }

                if (!$puedeAcceder) {
                    abort(403, 'No tienes acceso a este indicador.');
                }
            }

            if ($norma && is_object($norma) && method_exists($norma, 'departamento')) {
                if ($norma->departamento->id !== $userDeptId) {
                    abort(403, 'No tienes acceso a esta norma.');
                }
            }

            if ($encuesta && is_object($encuesta) && method_exists($encuesta, 'departamento')) {
                if ($encuesta->departamento->id !== $userDeptId) {
                    abort(403, 'No tienes acceso a esta encuesta.');
                }
            }

            if ($apartado && is_object($apartado) && method_exists($apartado, 'norma')) {
                $normaRel = $apartado->norma;
                if ($normaRel && $normaRel->departamento->id !== $userDeptId) {
                    abort(403, 'No tienes acceso a este apartado.');
                }
            }

            if ($departamento && is_object($departamento)) {
                if ($departamento->id !== $userDeptId) {
                    abort(403, 'No tienes acceso a este departamento.');
                }
            }
        }

        return $next($request);
    }
}
