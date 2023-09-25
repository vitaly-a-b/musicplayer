
<?=$this->modals?>

<?php  if(!empty($_SESSION['res']['answer'])): ?>

    <div class="wq-message__wrap">
        <?=$_SESSION['res']['answer']?>
    </div>

    <?php unset($_SESSION['res'])?>
<?php   endif;?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<?php $this->getScripts()?>

</body>

</html>