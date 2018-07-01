<?php $classementIndiv = @$_POST['dataIndiv'];?>
<?php $classementPromo = @$_POST['dataPromo'];?>
<?php $classementCollec = @$_POST['dataCollec'];?>

<div class="row">
  <h3 id="titreClassement" class="titlePage"></h3>

<table id="tabParisColl" class="classementTable table table-hover table-sm no-gutter"  style="display:none">
        <tbody>
          <tr>
            <th></th>
            <th>Promo</th>
            <th>Points</th>
            <th>Nb paris</th>
            <th>Moyenne points/paris</th>
          </tr>
          <?php foreach ($classementCollec as $key => $value): ?>
            <tr>
              <td><?php echo $key + 1; ?></td>
              <td><?php echo $value['promotion']; ?></td>
              <td><?php echo $value['total']; ?></td>
              <td><?php echo $value['nb']; ?></td>
              <td><?php echo $value['moyenne']; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
</div>
