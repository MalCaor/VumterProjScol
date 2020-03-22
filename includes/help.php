<?php

function getHelpText($helpId)
{   

    $brand = $_SESSION['brand'];
    
    if( $brand == "focal"){
        $contents = file_get_contents(dirname(__FILE__)."/json/ContextualHelp_FOCAL.json");        
    }else{
        $contents = file_get_contents(dirname(__FILE__)."/json/ContextualHelp_StormAudio.json");        
    };

    $json = json_decode($contents, true);
    
    return($json[$helpId]) ?: "no help available for $helpId" ;
}

function helpSpan($helpId, $side='right', $extra_class=null)
{
    $span = '<span class="%s" title="%s">'.
            '<img src="/img/icon-help.png" alt=""></span>';
    $class = "tooltip-icon tooltip-$side $extra_class";
    echo sprintf($span, $class, getHelpText($helpId));
}

function warnSpan($helpId, $side='right', $extra_class=null)
{
    $span = '<span class="%s" title="%s">'.
            '<img src="/img/icon-warning.png" alt=""></span>';
    $class = "tooltip-icon tooltip-$side warning $extra_class";
    echo sprintf($span, $class, getHelpText($helpId));
}
