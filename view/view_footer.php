<?php
echo '</div>'; // container-fluid
?>
<script>
function logout() {
  $.ajax({
    url: path.model + 'login.php',
    type: 'POST',
    dataType: 'json',
    data: { action: 'logout' },
    success: function(dat) {
      location.href = '../index.php';
    },
    error: function() {
      location.href = '../index.php';
    }
  });
}
</script>
<?php $oWeb->End(); ?>
