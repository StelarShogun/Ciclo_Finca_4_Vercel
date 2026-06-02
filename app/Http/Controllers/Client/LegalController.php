<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
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
        return Inertia::render('Client/Legal/Privacy', [
            'legalTitle' => 'Política de privacidad',
            'legalUpdated' => 'mayo 2026',
            'businessName' => config('cf4_legal.business_name'),
            'contactEmail' => config('cf4_legal.contact_email'),
        ]);
    }

    public function returns()
    {
        return Inertia::render('Client/Legal/Returns', [
            'legalTitle' => 'Cambios, devoluciones y cancelaciones',
            'legalUpdated' => 'mayo 2026',
            'businessName' => config('cf4_legal.business_name'),
        ]);
    }

    public function contact()
    {
        return Inertia::render('Client/Legal/Contact', [
            'legalTitle' => 'Contacto',
            'legal' => config('cf4_legal'),
        ]);
    }
}
