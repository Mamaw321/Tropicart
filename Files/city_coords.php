<?php
// STATIC CITY → COORDS MAP (PHILIPPINES)
function getCityCoordinates($city) {
    $map = [

        // NCR
        "Manila"        => ["lat" => 14.5995, "lng" => 120.9842],
        "Quezon City"   => ["lat" => 14.6760, "lng" => 121.0437],
        "Makati"        => ["lat" => 14.5547, "lng" => 121.0244],
        "Pasig"         => ["lat" => 14.5764, "lng" => 121.0851],
        "Taguig"        => ["lat" => 14.5176, "lng" => 121.0509],

        // Luzon
        "Baguio"        => ["lat" => 16.4023, "lng" => 120.5960],
        "Vigan"         => ["lat" => 17.5730, "lng" => 120.3866],
        "Tarlac"        => ["lat" => 15.4755, "lng" => 120.5963],
        "Pampanga"      => ["lat" => 15.0794, "lng" => 120.6190],
        "Bulacan"       => ["lat" => 14.7942, "lng" => 120.8799],

        // Visayas
        "Cebu City"     => ["lat" => 10.3157, "lng" => 123.8854],
        "Iloilo City"   => ["lat" => 10.7202, "lng" => 122.5621],
        "Tacloban"      => ["lat" => 11.2400, "lng" => 125.0000],

        // Mindanao
        "Davao City"    => ["lat" => 7.1907,  "lng" => 125.4553],
        "Cagayan de Oro"=> ["lat" => 8.4542,  "lng" => 124.6319],
        "Zamboanga"     => ["lat" => 6.9100,  "lng" => 122.0800],
    ];

    return $map[$city] ?? null;
}
?>
