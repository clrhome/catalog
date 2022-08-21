<div id="t{$element.prefix}">
  <div class="keys">
    {foreach from=$element.elements item=element}{if $element.idSafe}{include file='token_link.tpl'}{/if}{if $element.prefix}{include file='table_link.tpl'}{/if}
{/foreach}  </div>
  <div class="values">
    <div></div>
    {foreach from=$element.elements item=element}{if $element.idSafe}{include file='token.tpl'}{/if}{if $element.prefix}{include file='table.tpl'}{/if}
{/foreach}  </div>
</div>
