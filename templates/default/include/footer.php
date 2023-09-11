
<?=$this->modals?>

<?php  if(!empty($_SESSION['res']['answer'])): ?>

    <div class="wq-message__wrap">
        <?=$_SESSION['res']['answer']?>
    </div>

    <?php unset($_SESSION['res'])?>
<?php   endif;?>

<?php $this->getScripts()?>

</body>

</html>