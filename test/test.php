<?php
// Funktion för att addera två tal
function add($a, $b) {
    return $a + $b;
}

// Kontrollera om add-funktionen fungerar som förväntat
if (add(2, 3) === 5) {
    echo "Test passed!\n";
    exit(0); // Lyckas
} else {
    echo "Test failed!\n";
    exit(1); // Misslyckas
}
?>
