<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ClientLegalController extends Controller
{
    public function terms(): Response
    {
        return Inertia::render('Client/Legal/Terms', [
            'legalTitle' => 'Términos y condiciones',
            'legalUpdated' => 'mayo 2026',
            'businessName' => config('cf4_legal.business_name'),
            'contactEmail' => config('cf4_legal.contact_email'),
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
