{$divid_notes = "notes_{uniqid()}"}
<div id="{$divid_notes}" style="background-color:rgb(255,255,255);">
{if isset($message_notes.$message_id) && is_array($message_notes.$message_id)}
	{foreach from=$message_notes.$message_id item=note name=notes key=note_id}
	<div id="comment{$note->id}">
		{include file="devblocks:cerberusweb.core::internal/comments/note.tpl"}
	</div>
	{/foreach}
{/if}
</div>