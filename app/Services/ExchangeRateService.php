<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\ExchangeRateHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ExchangeRateService
{
    /**
     * Devuelve el catálogo de divisas disponibles.
     */
    public function getAvailableCurrencies(): array
    {
        return [
            // Principales Globales
            ['code' => 'MXN', 'symbol' => '$',    'name' => 'Peso Mexicano',          'flag' => 'MX', 'locale' => 'es-MX'],
            ['code' => 'USD', 'symbol' => 'USD$', 'name' => 'Dólar Estadounidense',   'flag' => 'US', 'locale' => 'en-US'],
            ['code' => 'EUR', 'symbol' => '€',    'name' => 'Euro',                   'flag' => 'EU', 'locale' => 'es-ES'],
            ['code' => 'GBP', 'symbol' => '£',    'name' => 'Libra Esterlina',        'flag' => 'GB', 'locale' => 'en-GB'],
            ['code' => 'JPY', 'symbol' => '¥',    'name' => 'Yen Japonés',            'flag' => 'JP', 'locale' => 'ja-JP'],
            ['code' => 'CAD', 'symbol' => 'CAD$', 'name' => 'Dólar Canadiense',       'flag' => 'CA', 'locale' => 'en-CA'],
            ['code' => 'CHF', 'symbol' => 'CHF',  'name' => 'Franco Suizo',           'flag' => 'CH', 'locale' => 'de-CH'],
            ['code' => 'CNY', 'symbol' => '¥',    'name' => 'Yuan Chino',             'flag' => 'CN', 'locale' => 'zh-CN'],
            ['code' => 'AUD', 'symbol' => 'A$',   'name' => 'Dólar Australiano',      'flag' => 'AU', 'locale' => 'en-AU'],
            
            // Latinoamérica y Caribe
            ['code' => 'BRL', 'symbol' => 'R$',   'name' => 'Real Brasileño',         'flag' => 'BR', 'locale' => 'pt-BR'],
            ['code' => 'ARS', 'symbol' => 'ARS$', 'name' => 'Peso Argentino',         'flag' => 'AR', 'locale' => 'es-AR'],
            ['code' => 'COP', 'symbol' => 'COP$', 'name' => 'Peso Colombiano',        'flag' => 'CO', 'locale' => 'es-CO'],
            ['code' => 'CLP', 'symbol' => 'CLP$', 'name' => 'Peso Chileno',           'flag' => 'CL', 'locale' => 'es-CL'],
            ['code' => 'PEN', 'symbol' => 'S/',   'name' => 'Sol Peruano',            'flag' => 'PE', 'locale' => 'es-PE'],
            ['code' => 'UYU', 'symbol' => '$U',   'name' => 'Peso Uruguayo',          'flag' => 'UY', 'locale' => 'es-UY'],
            ['code' => 'PAB', 'symbol' => 'B/.',  'name' => 'Balboa Panameño',        'flag' => 'PA', 'locale' => 'es-PA'],
            ['code' => 'CRC', 'symbol' => '₡',    'name' => 'Colón Costarricense',    'flag' => 'CR', 'locale' => 'es-CR'],
            ['code' => 'DOP', 'symbol' => 'RD$',  'name' => 'Peso Dominicano',        'flag' => 'DO', 'locale' => 'es-DO'],
            ['code' => 'GTQ', 'symbol' => 'Q',    'name' => 'Quetzal Guatemalteco',   'flag' => 'GT', 'locale' => 'es-GT'],
            ['code' => 'HNL', 'symbol' => 'L',    'name' => 'Lempira Hondureño',      'flag' => 'HN', 'locale' => 'es-HN'],
            ['code' => 'NIO', 'symbol' => 'C$',   'name' => 'Córdoba Nicaragüense',   'flag' => 'NI', 'locale' => 'es-NI'],
            ['code' => 'PYG', 'symbol' => '₲',    'name' => 'Guaraní Paraguayo',      'flag' => 'PY', 'locale' => 'es-PY'],
            ['code' => 'BOB', 'symbol' => 'Bs.',  'name' => 'Boliviano',              'flag' => 'BO', 'locale' => 'es-BO'],
            ['code' => 'VES', 'symbol' => 'Bs.S', 'name' => 'Bolívar Soberano',       'flag' => 'VE', 'locale' => 'es-VE'],
            
            // Otras Importantes Globalmente
            ['code' => 'INR', 'symbol' => '₹',    'name' => 'Rupia India',            'flag' => 'IN', 'locale' => 'en-IN'],
            ['code' => 'RUB', 'symbol' => '₽',    'name' => 'Rublo Ruso',             'flag' => 'RU', 'locale' => 'ru-RU'],
            ['code' => 'KRW', 'symbol' => '₩',    'name' => 'Won Surcoreano',         'flag' => 'KR', 'locale' => 'ko-KR'],
            ['code' => 'SGD', 'symbol' => 'S$',   'name' => 'Dólar Singapurense',     'flag' => 'SG', 'locale' => 'en-SG'],
            ['code' => 'NZD', 'symbol' => 'NZ$',  'name' => 'Dólar Neozelandés',      'flag' => 'NZ', 'locale' => 'en-NZ'],
            ['code' => 'ZAR', 'symbol' => 'R',    'name' => 'Rand Sudafricano',       'flag' => 'ZA', 'locale' => 'en-ZA'],
            ['code' => 'AED', 'symbol' => 'د.إ',   'name' => 'Dírham de los EAU',      'flag' => 'AE', 'locale' => 'ar-AE'],
        ];
    }

    /**
     * Obtiene y almacena todos los tipos de cambio de la BD.
     */
    public function getAllRates()
    {
        return ExchangeRate::all();
    }

    /**
     * Almacena manualmente un tipo de cambio (ej. ingresado por un administrador).
     */
    public function storeRate(array $data)
    {
        $currency = strtoupper($data['currency']);
        $rate = $data['rate'];
        $isLive = $data['is_live'] ?? false;
        
        $prev = ExchangeRate::where('currency', $currency)->first();
        if (!$prev || $prev->rate != $rate || $prev->is_live != $isLive) {
            ExchangeRateHistory::create([
                'currency' => $currency,
                'previous_rate' => $prev?->rate,
                'rate' => $rate,
                'is_live' => $isLive,
                'user_id' => auth()->id() ?? 'system'
            ]);
        }

        return ExchangeRate::updateOrCreate(
            ['currency' => $currency],
            ['rate' => $rate, 'is_live' => $isLive]
        );
    }

    /**
     * Elimina completamente la configuración de una moneda de la BD.
     */
    public function deleteRate(string $currency)
    {
        $currency = strtoupper($currency);
        $prev = ExchangeRate::where('currency', $currency)->first();

        ExchangeRateHistory::create([
            'currency' => $currency,
            'previous_rate' => $prev?->rate,
            'rate' => 0,
            'is_live' => false,
            'user_id' => auth()->id() ?? 'system'
        ]);

        ExchangeRate::where('currency', $currency)->delete();
    }

    /**
     * Devuelve el historial de cambios con información del usuario.
     */
    public function getHistory()
    {
        return ExchangeRateHistory::with('user:id,name,role')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Consulta a la API oficial (er-api), con cacheo de 12 horas,
     * actualiza la BD y retorna los registros nuevos.
     */
    public function fetchAndStoreLiveRates()
    {
        // 1. Consultar API (y cachear por 12 horas)
        $data = Cache::remember('er_api_mxn_rates', now()->addHours(12), function () {
            $response = Http::get('https://open.er-api.com/v6/latest/MXN');
            if (!$response->successful()) {
                throw new \Exception('API error');
            }
            return $response->json();
        });

        $rates = $data['rates'] ?? [];
        // 2. Determinar qué monedas actualizar:
        // Solo actualizamos las que ya existen en la base de datos.
        // No se inyectan monedas por defecto para evitar crear registros no solicitados.
        $activeCurrencies = ExchangeRate::pluck('currency')->toArray();

        // 3. Reemplazar o insertar los rates para cada moneda activa
        $updated = [];
        foreach ($activeCurrencies as $curr) {
            if ($curr !== 'MXN' && isset($rates[$curr])) {
                $prev = ExchangeRate::where('currency', $curr)->first();
                
                // Si la moneda está configurada manualmente, el cronjob no debe sobrescribirla.
                // Se mantiene su valor manual intacto.
                if ($prev && !$prev->is_live) {
                    continue;
                }

                $mxnPerUnit = 1 / $rates[$curr];
                
                // Only log if the rate effectively changed (using a small epsilon for precision, or exact match)
                if (!$prev || round($prev->rate, 4) != round($mxnPerUnit, 4) || !$prev->is_live) {
                    ExchangeRateHistory::create([
                        'currency' => $curr,
                        'previous_rate' => $prev?->rate,
                        'rate' => $mxnPerUnit,
                        'is_live' => true,
                        'user_id' => 'system'
                    ]);
                }

                $updated[] = ExchangeRate::updateOrCreate(
                    ['currency' => $curr],
                    ['rate' => $mxnPerUnit, 'is_live' => true]
                );
            }
        }

        return $updated;
    }
}
