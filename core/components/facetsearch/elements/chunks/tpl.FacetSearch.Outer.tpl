<div class="facetsearch-outer" data-hash="{$hash}" data-category_id="{$category_id}">
  <div class="row">
    <div class="col-md-3">
      <form action="{$_modx->resource.id | url}" method="post" class="facetsearch-filter-form">
        {$filters}
        {if $filters}
          <div class="d-flex justify-content-between">
              <button type="reset" class="btn btn-light btn_reset">Сбросить</button>
              <button type="submit" class="btn btn-primary hidden">Отправить</button>
          </div>
        {/if}
      </form>
    </div>
    <div class="col-md-9">
      <h3>Всего результатов: <span class="facetsearch-total">{$total}</span></h3>
      <div class="row">
        <div class="facetsearch_sort span5 col-md-5">
          Сортировка:
            <a href="#" data-sort="pagetitle" data-dir="{if $sorts['pagetitle:asc']}asc{/if}" data-default="asc" class="sort">
            По наименованию
            <span></span>
            <a href="#" data-sort="publishedon" data-dir="{if $sorts['publishedon:asc']}asc{/if}" data-default="asc" class="sort">
            По публикации
            <span></span>
          </a>
        </div>
        <div class="col-md-5">Показывать на странице
          <select name="facetsearch_limit" class="facetsearch_limit">
            <option value="10" {if $limit == 10}selected{/if}>10</option>
            <option value="15" {if $limit == 15}selected{/if}>15</option>
            <option value="25" {if $limit == 25}selected{/if}>25</option>
            <option value="50" {if $limit == 50}selected{/if}>50</option>
            <option value="100" {if $limit == 100}selected{/if}>100</option>
          </select>
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
  {if $log}
    <div class="row">
      <div class="col-md-12">
        <pre class="FacetSearchLog">{$log}</pre>
      </div>
    </div>
  {/if}
</div>