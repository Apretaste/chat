<h1>Charla con @{$username}</h1>

<table width="100%" cellspacing="0" cellpadding="5" border=0>
{foreach item = item from=$notes}
	<tr><td {if $username == $item->from_username}bgcolor="#F2F2F2"{/if}>
    	<span style="color: #AAAAAA;"><small>{$item->date}</small></span><br/>
    	<b>{link href="PERFIL @{$item->from_username}" caption = "@{$item->from_username}"}</b>:   
    	<span style="color:{if $username == $item->from_username} #000000 {else} #000066 {/if};">{$item->text}</span>
    </td></tr>
{/foreach}
</table>

{space30}

<center>
	{button href="NOTA @{$username} Reemplace este texto por su nota" caption="Responder" body="" color="green" size="large"}
</center>
