<?php

namespace App\Http\Controllers;

class ClientLegalController extends Controller
{
    public function terms()
    {
        return view('client.legal.terms', [
            'legalTitle' => 'Términos y condiciones',
            'legalUpdated' => 'mayo 2026',
        ]);
    }

    public function privacy()
    {
        return view('client.legal.privacy', [
            'legalTitle' => 'Política de privacidad',
            'legalUpdated' => 'mayo 2026',
        ]);
    }

    public function returns()
    {
        return view('client.legal.returns', [
            'legalTitle' => 'Cambios, devoluciones y cancelaciones',
            'legalUpdated' => 'mayo 2026',
        ]);
    }

    public function contact()
    {
        return view('client.legal.contact', [
            'legalTitle' => 'Contacto',
            'legal' => config('cf4_legal'),
        ]);
    }
}
