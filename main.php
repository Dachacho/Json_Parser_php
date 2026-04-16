<?php

enum TokenType
{
    case LEFT_BRACKET;
    case RIGHT_BRACKET;
    case STRING;
    case NUMBER;
    case COMMA;
    case COLON;
    case QUOTE;
}

$file = '';

if(isset($argv[1]))
{
    $file = file_get_contents($argv[1]);
}else{
    throw new Exception("couldn't read file");
}

$tokens = [];

$i = 0;
$length = strlen($file);

while ($i < $length)
{
    $char = $file[$i];

    switch($char)
    {
        case(ctype_space($char)):
            $i++;
            break; 
        case($char === '{'):
            $i++;
            $tokens[] = ['TYPE' => TokenType::LEFT_BRACKET, 'VALUE' => '{'];
            break;
        case($char === '}'):
            $i++;
            $tokens[] = ['TYPE' => TokenType::RIGHT_BRACKET, 'VALUE' => '}'];
            break; 
        case($char === ':'):
            $i++;
            $tokens[] = ['TYPE' => TokenType::COLON, 'VALUE' => ':'];
            break;
        case($char === ','):
            $i++;
            $tokens[] = ['TYPE' => TokenType::COMMA, 'VALUE' => ','];
            break;
        case($char === '"'):
            //skip first quote
            $i++;

            $start = $i;
            while($i < $length)
            {
                $c = $file[$i];

                if($c === '\n' || $c === '\r')
                {
                    throw new Exception("unterminated string (new line before closing quote) at index: $start");
                }

                if($c === '"')
                {
                    break;
                }

                $i++;
            }

            if($i >= $length)
            {
                throw new Exception("Unterminated string starting at index: $start");
            }
            
            $str_value = substr($file, $start, $i - $start);
            $tokens[] = ['TYPE' => TokenType::STRING, 'VALUE' => $str_value];

            //skip closing quote
            $i++;
    }
}

var_dump($tokens);