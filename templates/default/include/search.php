
<div class="module-search">

    <form id="main-search" action="<?=$this->alias('search')?>" method="get" data-form-search>
        <div class="form">
            <div class="inputSearch">
                <div class="inInputSearch">
                    <input class="form-control" type="search" name="search" value="" id="search" placeholder="Поиск музыки" aria-label="Поиск">
                    <div class="name_focus">
                        <select name="choice" class="searchArtist form-select form-select-sm">
                            <option value="name">По названию</option>
                            <option value="artist">По исполнителю</option>

                        </select>
                        <button class="btn btn-primary btn-lg" title="Начать поиск музыки" type="submit">Найти</button>
                    </div>
                    <div class="dropdownButton"></div>

                </div>


            </div>
        </div>
    </form>
</div>