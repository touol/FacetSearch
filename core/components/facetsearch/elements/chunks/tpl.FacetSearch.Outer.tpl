<div class="facetsearch-outer" data-hash="{$hash}" data-category_id="{$category_id}">
  <div class="row">
    <div class="col-md-3">
      {$filters}
    </div>
    <div class="col-md-9">
      <h3>Всего результатов: <span class="facetsearch-total">{$total}</span></h3>
      <div class="row">
        <div class="facetsearch_sort span5 col-md-5">
          Сортировка:
            <a href="#" data-sort="pagetitle" data-dir="{if $sort == 'pagetitle:asc'}asc{/if}" data-default="asc" class="sort">
            По наименованию
            <span></span>
          </a>
        </div>
      </div>

      <div class="facetsearch_selected_wrapper">
        <div class="facetsearch_selected">
          <span></span>
        </div>
      </div>
      <div class="facetsearch-results">
        {$results}
      </div>
      <div class="facetsearch_btm_more">
        
      </div>
      <div class="facetsearch_pagination">
        {$pagination}
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-12">
      <pre class="FacetSearchLog">{$log}</pre>
    </div>
  </div>
</div>