<?php

/** Function to translate the mode into a readable string
 * @param string $ModeName The abstract or readable string
 * @param bool $direction TRUE =  HC name => Readable string  //   FALSE = Readable string => HC name
 * @return string
 */
function DishwasherTranslateMode( $ModeName, $direction ) {
    $dictionary = array(
        "PreRinse" => "Vorspülen",
        "Auto1" => "Auto sanft",
        "Auto2" => "Auto",
        "Auto3" => "Auto hart",
        "Eco50" => "Eco 50°C",
        "Quick45" => "Schnell 45°C",
        "Quick65" => "Schnell 65°C",
        "Intensiv45" => "Intensiv 45°C",
        "Intensiv70" => "Intensiv 70°C",
        "Normal45" => "Normal 45°C",
        "Normal65" => "Normal 65°C",
        "Glas40" => "Glässer 40°C",
        "NightWash" => "Ruhemodus",
        "AutoHalfLoad" => "Auto halb voll",
        "IntensivPower" => "Intensiv stark",
        "MagicDaily" => "Tägliches waschen",
        "Kurz60" => "Kurz 60°C",
        "Super60" => "Super 60°C",
        "ExpressSparkle65" => "Extra sauber 65°C",
        "MachineCare" => "Maschinen Säuberung",
        "SteamFresh" => "Extra trocken",
        "MaximumCleaning" => "Extra sauber"
    );

    // Translate HC NAME => READABLE STRING
    if ( $direction ) { return $dictionary[$ModeName]; }

    // Translate READABLE STRING => HC NAME
    // rewrite dictionary
    return array_flip($dictionary)[$ModeName];
}

/** Function to translate the mode into a readable string
 * @param string $ModeName The abstract or readable string
 * @param bool $direction TRUE =  HC name => Readable string  //   FALSE = Readable string => HC name
 * @return string
 */
function OvenTranslateMode( $ModeName, $direction) {
    $dictionary = array(
        "PreHeating" => "Vorheizen",
        "HotAir" => "Umluft",
        "HotAirEco" => "Umluft Eco",
        "HotAirGrilling" => "Umluft grillen",
        "TopBottomHeating" => "Ober/-Unterhitze",
        "TopBottomHeatingEco" => "Ober/-Unterhitze Eco",
        "BottomHeating" => "Unterhitze",
        "PizzaSetting" => "Pizza",
        "SlowCook" => "Langsames kochen",
        "IntensiveHeat" => "Intensives heizen",
        "KeepWarm" => "Warm halten",
        "PreheatOvenware" => "Vorheizen Geschirr",
        "FrozenHeatupSpecial" => "Gefrorenes Aufheizen",
        "Desiccation" => "Extrem Trocknen",
        "Defrost" => "Auftauen",
        "Proof" => "Brot geringe Temperatur",
        "Dish" => "Gericht"
    );

    // Translate HC NAME => READABLE STRING
    if ( $direction ) { return $dictionary[$ModeName]; }

    // Translate READABLE STRING => HC NAME
    // rewrite dictionary
    return array_flip($dictionary)[$ModeName];
}

/** Function to translate the mode into a readable string
 * @param string $ModeName The abstract or readable string
 * @param bool $direction TRUE =  HC name => Readable string  //   FALSE = Readable string => HC name
 * @return string
 */
function DryerTranslateMode( $ModeName, $direction) {
    $dictionary = array(
        "Cotton" => "Baumwolle",
        "Synthetic" => "Synthetik",
        "Mix" => "Mischgewebe",
        "Blankets" => "Decken",
        "BusinessShirts" => "Business-Shirts",
        "DownFeathers" => "Daunenfedern",
        "Hygiene" => "Hygiene",
        "Jeans" => "Jeans",
        "Outdoor" => "Outdoor Kleidung",
        "SyntheticRefresh" => "Synthetische Auffrischung",
        "Towels" => "Handtücher",
        "Delicates" => "Feinfühlig",
        "Super40" => "Super light",
        "Shirts15" => "Shirts ohne geringe Temperatur",
        "AntiShrink" => "Anti Schrumpfen"
    );

    // Translate HC NAME => READABLE STRING
    if ( $direction ) { return $dictionary[$ModeName]; }

    // Translate READABLE STRING => HC NAME
    // rewrite dictionary
    return array_flip($dictionary)[$ModeName];
}