<?php include_once('header.php'); ?>
<div class="span3">
    <?php $_GET['page'] = 'bot'; include_once('sidebar.php'); ?>
</div>
<div class="well span9">
    <h1>My Bots</h1>    
        <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Server</th>
            <th>Channel</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td>Sample Bot</td>
            <td>chat.freenode.net</td>
            <td>#wildphp</td>
            <td>
                <div class="btn-group">
                    <a class="btn" href="#"><i class="icon-play"></i></a>
                    <a class="btn" href="#"><i class="icon-pencil"></i></a>
                    <a class="btn" href="#"><i class="icon-trash"></i></a>
                </div>
            </td>
          </tr>
        </tbody>
      </table>
</div>
<?php include_once('footer.php'); ?>