<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEstacionCentralRequest;
use App\Models\ActivityLog;
use App\Models\EstacionCentral;
use Illuminate\Support\Facades\Log;

class EstacionCentralController extends Controller
{
    public function edit()
    {
        $config = EstacionCentral::firstOrCreate([], [
            'nombre'       => 'Estación Central',
            'modo'         => 'ninguno',
            'radio_metros' => 150,
            'activo'       => false,
        ]);

        return view('admin.estacion_central', [
            'config'   => $config,
            'ipActual' => request()->ip(),
        ]);
    }

    public function update(UpdateEstacionCentralRequest $request)
    {
        $data = $request->validated();
        $data['activo']        = $request->boolean('activo');
        $data['registrado_por'] = $request->user()->id;

        $usaGps = in_array($data['modo'], ['gps', 'ambos'], true);
        if (!$usaGps) {
            $data['latitud']  = null;
            $data['longitud'] = null;
        }
        if ($data['modo'] === 'ninguno') {
            $data['ip_permitida'] = null;
        }

        $config = EstacionCentral::firstOrCreate([], []);
        $config->update($data);

        ActivityLog::log('estacion_central_config', 'Configuración de estación central: modo ' . $data['modo'], 'sistema', [
            'modo'          => $data['modo'],
            'activo'        => $data['activo'],
            'radio_metros'  => $data['radio_metros'],
            'ip_permitida'  => $data['ip_permitida'],
            'user_id'       => $request->user()->id,
        ]);

        Log::info('Config estación central actualizada', [
            'modo'   => $data['modo'],
            'activo' => $data['activo'],
        ]);

        return redirect()->route('admin.estacion.central')
            ->with('success', 'Configuración de la estación central actualizada correctamente.');
    }
}
