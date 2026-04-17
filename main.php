<?php

enum TokenType
{
    case OPEN_CURLY;
    case CLOSE_CURLY;
    case STRING;
    case NUMBER;
    case COMMA;
    case COLON;
    case QUOTE;
    case OPEN_BRACKET;
    case CLOSE_BRACKET;
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

    switch(true)
    {
        case(ctype_space($char)):
            $i++;
            break; 
        case($char === '{'):
            $i++;
            $tokens[] = ['TYPE' => TokenType::OPEN_CURLY, 'VALUE' => '{'];
            break;
        case($char === '}'):
            $i++;
            $tokens[] = ['TYPE' => TokenType::CLOSE_CURLY, 'VALUE' => '}'];
            break; 
        case($char === ':'):
            $i++;
            $tokens[] = ['TYPE' => TokenType::COLON, 'VALUE' => ':'];
            break;
        case($char === ','):
            $i++;
            $tokens[] = ['TYPE' => TokenType::COMMA, 'VALUE' => ','];
            break;
        case ($char === '['):
            $i++;
            $tokens[] = ['TYPE' => TokenType::OPEN_BRACKET, 'VALUE' => '['];
            break;
        case ($char === ']'):
            $i++;
            $tokens[] = ['TYPE' => TokenType::CLOSE_BRACKET, 'VALUE' => ']'];
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
            break;
        case ($char === '-') || (ctype_digit($char)):
            $start = $i;
            
            while($i < $length && ctype_digit($file[$i]))
            {
                $i++;
            }
            
            $num_value = substr($file, $start, $i - $start); 
            $tokens[] = ['TYPE' => TokenType::NUMBER, 'VALUE' => $num_value]; 
            break;
    }
}

$i = 0;

function parse_object(&$i, $tokens)
{
    $result = [];
    $i++;
    return parse_members($i, $tokens, $result);
}

function parse_members(&$i, $tokens, $acc)
{  
    if($tokens[$i]['TYPE'] === TokenType::CLOSE_CURLY)
    {
        $i++;
        return $acc;
    }

    [$key, $value] = parse_pair($i, $tokens);
    $acc[$key] = $value;

    while($tokens[$i]['TYPE'] === TokenType::COMMA)
    {
        $i++;
        [$key, $value] = parse_pair($i, $tokens);
        $acc[$key] = $value; 
    } 

    if($tokens[$i]['TYPE'] !== TokenType::CLOSE_CURLY)
    {
        throw new Exception("Expected '}' at the end");
    }

    $i++;
    return $acc;
}

function parse_pair(&$i, $tokens)
{
    if ($tokens[$i]['TYPE'] !== TokenType::STRING) {
        throw new Exception("Expected string key");
    }
    $key = $tokens[$i]['VALUE'];

    $i++;
    if($tokens[$i]['TYPE'] !== TokenType::COLON)
    {
        throw new Exception("Expected ':' after key");
    }

    $i++;
    $value = parse_value($i, $tokens);
    return [$key, $value];
}

function parse_array(&$i, $tokens)
{
    $i++;

    $acc[] = parse_value($i, $tokens);

    while($tokens[$i]['TYPE'] === TokenType::COMMA)
    {
        $i++;
        $acc[] = parse_value($i, $tokens);
    } 

    if($tokens[$i]['TYPE'] !== TokenType::CLOSE_BRACKET)
    {
        throw new Exception("Expected ']' at the end");
    }

    $i++;
    return $acc;
}

function parse_value(&$i, $tokens)
{
    $token = $tokens[$i];

    switch($token['TYPE'])
    {
        case TokenType::STRING:
            $i++;
            return $token['VALUE'];
        case TokenType::NUMBER:
            $i++;
            return (int)$token['VALUE'];
        case TokenType::OPEN_CURLY:
            return parse_object($i, $tokens);
        case TokenType::OPEN_BRACKET:
            return parse_array($i, $tokens);
        default:
            throw new Exception("Unexpected token");
    }
}

var_dump(parse_object($i, $tokens));