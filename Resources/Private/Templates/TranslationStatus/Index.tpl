{extends file='Layouts/Backend.tpl'}

{block name=header}
<div class="module-docheader-bar t3js-module-docheader-bar">
	<div class="module-docheader-bar module-docheader-bar-navigation form-inline">
		<div class="form-group form-group-sm">
			<select class="form-control" name="_fileSelector" onchange="if(this.options[this.selectedIndex].value){ window.location.href=(this.options[this.selectedIndex].value);}">
				{if !$currentFileName}
					<option>Datei auswählen</option>
				{/if}
				{foreach $languageFiles as $extensionName => $files}
					<optgroup label="{$extensionName}">
						{foreach $files as $fileName}
							<option value="{uri_action arguments=[extensionName => $extensionName, fileName => $fileName, showAllLabels => $currentShowAllLabels]}"{if $extensionName === $currentExtensionName && $fileName === $currentFileName} selected{/if}>{$fileName}</option>
						{/foreach}
					</optgroup>
				{/foreach}
			</select>
		</div>
	</div>
	{if $currentFileName}
		<div class="module-docheader-bar module-docheader-bar-buttons">
			<div class="module-docheader-bar-column-left">
				<div class="btn-toolbar">
					{$iconFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class)}
					<button class="btn btn-default btn-sm" form="exportForm">
						{$iconFactory->getIcon('actions-document-export-csv', \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL) nofilter}
					</button>
				</div>
			</div>
		</div>
	{/if}
</div>
{/block}

{block name=content}
{fluid}
<f:form action="export" id="exportForm">
	<f:form.hidden name="showAllLabels" value="{$currentShowAllLabels}" />
	<f:form.hidden name="extensionName" value="{$currentExtensionName}" />
	<f:form.hidden name="fileName" value="{$currentFileName}" />

	<h1>Übersetzungsstatus</h1>
	{if !$currentFileName}
		<p>
			Bitte wählen Sie eine Datei aus, die überprüft werden soll.
		</p>
	{else if isset($translationKeys)}
		<div class="translation-status__languages">
			{foreach array_keys($translations) as $languageKey}
				<label class="translation-status__checkbox">
					<f:form.checkbox name="languages[]" value="{$languageKey}" checked="true" additionalAttributes="{ data-index: {$languageKey@index + 2}}" /> {$languageKey}
				</label>
			{/foreach}
		</div>

		<div class="translation-status__filter">
			<f:form.textfield name="search" type="search" class="form-control" placeholder="Search…" />
		</div>

		<table class="table table-striped table-hover translation-status">
			<thead>
				<tr>
					<th>Key</th>
					{foreach array_keys($translations) as $languageKey}
						<th>{$languageKey}</th>
					{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach $translationKeys as $translationKey}
					<tr>
						<th>{$translationKey}</th>
					{foreach $translations as $languageKey => $translationData}
						{if isset($translationData[$translationKey])}
							<td>{$translationData[$translationKey]}</td>
						{else}
							<td class="danger">
								<em>missing</em>
							</td>
						{/if}
					{/foreach}
					</tr>
				{/foreach}
			</tbody>
		</table>
	{else}
		<p>Alle Labels sind in allen Sprachen übersetzt</p>
	{/if}

	<div class="typo3-listOptions">
		<div class="checkbox">
			<label for="check-show-all-translations">
				<input type="checkbox" class="checkbox" onclick="window.location.href = '{uri_action arguments=[extensionName => $currentExtensionName, fileName => $currentFileName, showAllLabels => !$currentShowAllLabels]}'; return false;" id="check-show-all-translations" value="1"{if $currentShowAllLabels} checked{/if}>Alle Labels anzeigen
			</label>
		</div>
	</div>
</f:form>
{/fluid}
{/block}
