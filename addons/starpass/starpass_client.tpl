
{if isset($return) }
    <meta http-equiv="refresh" content="5; url={$url}">
    <center>	
        <p>{$_lang.starpass_added}</p>
        <p>{$_lang.starpass_redirect} <a href="{$url}">{$_lang.click_here}</a>
    </center>	
{/if}
{if isset($purchase) }
    <center>
        <iframe 
            width="630"
            height="675"
            frameborder="0"
            src="{$url}"></iframe>
    </center>
{/if}