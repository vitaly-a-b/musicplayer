        <footer class="wq-footer">
            <div class="wq-footer__copy wq-copy">
                <p class="wq-copy__text"><?=(new \DateTime())->format('Y')?> © All rights received</p>
                <a href="#" target="_blank" class="wq-copy__link">Vitaly Barsukov</a>
            </div>
        </footer>

        <div class="wq-message__wrap">
            <?php
            if(isset($_SESSION['res']['answer'])){
                echo $_SESSION['res']['answer'];
                unset($_SESSION['res']);
            }
            ?>
        </div>

    </div>

</div>

<script src="https://api-maps.yandex.ru/2.1/?lang=ru-RU"></script>

<?php $this->getScripts()?>

</body>
</html>