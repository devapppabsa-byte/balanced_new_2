<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Imports\CamposForaneosImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\LogBalanced;
use App\Imports\InputPrecargadoImport;

class CamposForaneosImportController extends Controller
{
    
    public function importar(Request $request){


        $autor = 'Id: '.auth()->guard('admin')->user()->id.' - '.auth()->guard('admin')->user()->nombre .' - '. $puesto_autor = auth()->guard('admin')->user()->puesto;

        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls,csv'
        ]);

        $request->file('archivo')->storeAs('exports', 'ultimo_excel.xlsx', 'public');

        Excel::import(new InputPrecargadoImport, $request->file('archivo'));


        LogBalanced::create([
            'autor' => $autor,
            'accion' => "excel",
            'descripcion' => "Se cargo el archivo de excel",
            'ip' => request()->ip() 
        ]);


        return back()->with('success', 'El archivo fue cargado!');


    }


    public function descargar_ultimo_excel(){

        $filePath = storage_path('app/public/exports/ultimo_excel.xlsx');

        if (!file_exists($filePath)) {
            return back()->withErrors(['No hay un archivo de excel cargado aún.']);
        }

        return response()->download($filePath, 'PlantillaExcel.xlsx');

    }


}
