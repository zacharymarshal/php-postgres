<?php

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers([
        'short_array_syntax',
        'encoding',
        'whitespacy_lines',
        'unused_use',
        'return',
        'operators_spaces',
        'extra_empty_lines',
        'duplicate_semicolon',
        'concat_with_spaces',
        'ternary_spaces',
        'spaces_cast',
    ])
;
