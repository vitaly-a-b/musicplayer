
<div class="module-search">

    <form id="main-search" action="<?=$this->alias('search')?>" method="get">
        <div class="form">
            <div class="inputSearch">
                <div class="inInputSearch">
                    <input type="search" name="search" value="" id="search" placeholder="Поиск музыки" aria-label="Поиск">
                    <div class="dropdown name_focus">
                        <select name="choice" class="searchArtist">
                            <option value="name">По названию</option>
                            <option value="artist">По исполнителю</option>

                        </select>
                        <button title="Начать поиск музыки" type="submit">Найти</button>
                    </div>
                    <div class="dropdownButton"></div>

                </div>


            </div>
        </div>
    </form>
</div>