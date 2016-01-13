<h1>Conversaci&oacute;n entre @{$username} y t&uacute;</h1>

{foreach item = item from=$notes}
    <p align="right"><span style="color: #AAAAAA;"><small>{$item->date}</small></span></p>
    <p><b>{link href="PERFIL {$item->from_username}" caption = "@{$item->from_username}"}</b>:  
    <span style="color:{if $username == $item->from_username} #000000 {else} #000066 {/if};">{$item->text}</span>
    </p>
{/foreach}

<center>{button href="NOTA {$username} He leido tu nota'" caption="Responder" color="green" size="large"}
</center>