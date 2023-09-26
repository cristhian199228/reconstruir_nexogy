<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use SoapClient;
use App\Models\LlamadaNexogy;
use DateTimeZone;
use DateTime;
use DateInterval;
use Exception;

class ReconstruirNexogy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reconstruir-nexogy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerid = '7a181d99-d3fd-4a5e-9a23-413f4ba3159e';
        $apikey = 'a3c5db42-2fc5-4b26-ae67-667d1f4625bb';
        $siteid = '55931d42-f510-4342-a790-93e45a8f9b27';

        $files = Storage::disk('ftp')->files('NEXOGY');

        foreach ($files as $file) {

            $palabrasAQuitar = ["NEXOGY/", ".wav"];
            $nuevoString = str_replace($palabrasAQuitar, "", $file);


            if (LlamadaNexogy::where('CallId', '=', $nuevoString)->exists()) {
                echo 'registro ya existe';
                Storage::disk('ftp')->move( $file, 'NEXOGY_PROCESADOS/' . $file);
            } else {
                try {
                    $client = new SoapClient('https://api.callcabinet.com/APIServices/CallListingService.svc?wsdl');
                    $params = array(
                        "CustomerID" => $customerid,
                        "APIKey" =>  $apikey,
                        "SiteID" =>  $siteid,
                        'CallID' => $nuevoString
                    );
    
                    $respuesta_ws = $client->GetListOfCallsWithSearch($params);
                    $llamada = $respuesta_ws->GetListOfCallsWithSearchResult->CallListingAPIEntry;
    
                    $nexogy = new LlamadaNexogy();
                    $nexogy->AgentName = $llamada->AgentName;
                    $nexogy->CallId = $llamada->CallId;
                    $nexogy->CallerId = $llamada->CallerID;
                    $nexogy->CustomerInternalRef = $llamada->CustomerInternalRef;
                    $nexogy->DTMF = $llamada->DTMF;
                    $nexogy->DiD = $llamada->DiD;
                    $nexogy->Direction = $llamada->Direction;
                    $nexogy->Duration = $llamada->Duration;
                    $nexogy->Extension = $llamada->Extension;
                    $nexogy->Flagged = $llamada->Flagged;
                    $nexogy->PhoneNumber = $llamada->PhoneNumber;
                    $nexogy->RecordingAvailable = $llamada->RecordingAvailable;
                    $nexogy->StartTime = $llamada->StartTime;
                    $nexogy->save();
    
                    Storage::disk('ftp')->move( $file, 'NEXOGY_PROCESADOS/' . $file);
                } catch (\Exception $e) {
                    // Manejo de excepciones de tipo Exception
                }
               
            }
        }
    }
}
