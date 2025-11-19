<?php
// Converts a Phred .phd file into FASTQ.

if ($argc < 2) {
    fwrite(STDERR, "Usage: php {$argv[0]} input.phd > output.fastq\n");
    exit(1);
}

$infile = $argv[1];
if (!is_readable($infile)) {
    fwrite(STDERR, "Cannot read file: $infile\n");
    exit(1);
}

// Read input file
$txt = file_get_contents($infile);
// Normalize line endings
$txt = str_replace(["\r\n", "\r"], "\n", $txt);

// Extract sequence name
$name = "seq";
if (preg_match('/^BEGIN_SEQUENCE\s+(\S+)/m', $txt, $m)) {
    $name = $m[1];
}

// Extract DNA block
if (!preg_match('/BEGIN_DNA(.*)END_DNA/s', $txt, $m)) {
    fwrite(STDERR, "ERROR: No BEGIN_DNA ... END_DNA block found.\n");
    exit(1);
}

$dna_block = trim($m[1]);

$seq = "";
$quals = [];

foreach (explode("\n", $dna_block) as $line) {
    $line = trim($line);
    if ($line === "") continue;

    // Expected: <base> <quality> <peak_index>
    if (preg_match('/^([acgtACGTnN])\s+(\d+)\s+(\d+)/', $line, $m)) {
        $base = strtoupper($m[1]);
        $q = (int)$m[2];

        $seq .= $base;
        $quals[] = $q;
    }
}

// Build FASTQ quality string (Phred+33)
$qualstr = "";
foreach ($quals as $q) {
    if ($q < 0) $q = 0;
    if ($q > 93) $q = 93; // safe printable limit
    $qualstr .= chr($q + 33);
}

// Output FASTQ
echo "@$name\n";
echo $seq . "\n";
echo "+\n";
echo $qualstr . "\n";
