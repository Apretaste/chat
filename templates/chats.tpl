<h1>Charla con @{$friendUsername}</h1>

{if not $chats}
	<p>Usted no ha chateado con @{$friendUsername} anteriormente. Presione el bot&oacute;n a continuaci&oacute;n para enviarle una primera nota.</p>
{else}
	<table width="100%" cellspacing="0" cellpadding="5" border=0>
	{foreach item=item from=$chats}
		{assign var="color" value="black"}
		{if $item->gender eq "M"}{assign var="color" value="#4863A0"}{/if}
		{if $item->gender eq "F"}{assign var="color" value="#F778A1"}{/if}

		<tr {if $friendUsername == $item->username}bgcolor="#F2F2F2"{/if}>
			<!--PICTURE-->
			{if $APRETASTE_ENVIRONMENT eq "web"}
			<td width="1" valign="top">
				<img class="profile-small" src="{$item->picture}" title="@{$item->username}" alt="@{$item->username}"/>
			</td>
			{/if}
			<td>
				<!--USERNAME AND DATE SENT-->
				<span style="color:grey; font-size:10px;">
					{link href="PERFIL @{$item->username}" caption="@{$item->username}" style="color:{$color};"}
					{$item->sent|date_format:"%e/%m/%Y %I:%M %p"}
				</span><br/>

				<!--TEXT-->
				<span style="color:{if $friendUsername == $item->username}#000000{else}#000066{/if};">
					{$item->text}
				</span>
			</td>
		</tr>
	{/foreach}
	</table>
{/if}

{space15}

<center>
	{button href="CHAT @{$friendUsername}" caption="Escribir" desc="a:Escriba el texto a enviar*" popup="true" wait="false"}
	{button href="CHAT" caption="Mis chats" color="grey"}
</center>

{if $APRETASTE_ENVIRONMENT eq "web"}
<style type="text/css">
	.profile-small{
		width:28px;
		height:28px;
		border-radius:100px;
	}
</style>
{/if}
