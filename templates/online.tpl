<h1>Usuarios conectados</h1>
<table cellspacing="0" cellpadding="10" border="0" width="100%">
{foreach item=item from=$users}
<tr>
    <td>{link caption="{$item->username}" href="PERFIL @{$item->username}"}</td>
    <td>{$item->province_code}</td>
    <td>{if $item->gender eq "M"}<font color="#4863A0">M</font>{/if}
        {if $item->gender eq "F"}<font color=#F778A1>F</font>{/if}</td>
    <td align="right">{button href="CHAT @{$item->username}" caption="chatear" size="small"}</td>
</tr>
{foreachelse}
<p>No hay usuarios conectados en este momento. Vuelva m&aacute;s tarde.</p>
{/foreach}
</table>