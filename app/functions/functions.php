<?php

function conn(){
    $mysqli = new mysqli("localhost", "letterwinds_jr", "HNo_BLH~AWIM", "letterwinds_productiondb");
    if ($mysqli->connect_errno) {
        return "Fallo al conectar a MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }else{
        return $mysqli;
    }
}

function isMultidimensional(array $array): bool {
    foreach ($array as $element) {
        if (is_array($element)) {
            return true; // Es multidimensional
        }
    }
    return false; // Es simple
}

function jrMysqli($consulta, $id1=null, $id2=null){
    $conn = conn();
    $stmt = $conn->prepare($consulta);
    if($id1!==null && $id2!==null){
        $stmt->bind_param('ss', $id1, $id2);
    }else if($id1!==null && $id2==null){
        $stmt->bind_param('s', $id1);
    };
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $rows = $result->fetch_assoc(); // Devolver una sola fila como array asociativo
        if(count($rows)===1){
            $row = reset($rows);
        }else{
            $row = $rows;
        }
    } else {
        $row = $result->fetch_all(MYSQLI_ASSOC); // Devolver todas las filas como array multidimensional
    }
    $stmt->close();
    $conn->close();
    return $row;
}

function getFolderName($folderId){
    $conn = conn();
    $stmt = $conn->prepare("SELECT * FROM folders WHERE id = ?");
    $stmt->bind_param('s', $folderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row['folder_name'];
}

function folders_list(){
    $user_id = $_SESSION['id_user'];
    //Listado de Carpetas
    $consult = jrMysqli("SELECT * FROM folders WHERE id_owner=?", $user_id);
    echo '<div class="col-lg-3 col-md-3 border-end pt-4">
        <h5>Carpetas</h5>
        <div id="folders-list-container">';
    if(!empty($consult)){
        if(isMultidimensional($consult)===false){
            // if(!isset($_GET['action']) || $_GET['action']=='openfolder' || $_GET['action']=='dash'){
            //     if(isset($_GET['folderid']) && $_GET['folderid'] == $consult['id']){
            //         $icon = '<i class="bi bi-folder2-open"></i> ';
            //         $color = 'jr-btn-opened-folder';
            //     }else{
            //         $icon = '<i class="bi bi-folder"></i> ';
            //         $color = 'btn-light';
            //     }
            // }
            echo '<div class="btn btn-light p-2 mb-1 cyan-100 align-middle" id="folder-'.$consult['id'].'">
                    <i class="bi bi-folder"></i>
                    <a class="btn p-0 jr-dash-folders-list-folder-name" onclick="loadFolder(\''.$consult['id'].'\')">'.$consult['folder_name'].'</a>
                    <i class="ms-2 bi bi-three-dots" onclick="folder_options(this, '.$consult['id'].');"></i>
                    <div class="folder-options-menu" id="folder-options-'.$consult['id'].'" style="display: none;"></div>
                    </div><br>';
        }else{
            foreach($consult as $result){
                // if(!isset($_GET['action']) || $_GET['action']=='openfolder' || $_GET['action']=='dash'){
                //     if(isset($_GET['folderid']) && $_GET['folderid'] == $result['id']){
                //         $icon = '<i class="bi bi-folder2-open"></i> ';
                //         $color = 'jr-btn-opened-folder';
                //     }else{
                //         $icon = '<i class="bi bi-folder"></i> ';
                //         $color = 'btn-light';
                //     }
                // }
                echo '<div class="btn btn-light p-2 mb-1 cyan-100 align-middle" id="folder-'.$result['id'].'">
                        <i class="bi bi-folder"></i>
                        <a class="btn p-0 jr-dash-folders-list-folder-name" onclick="loadFolder(\''.$result['id'].'\')">'.$result['folder_name'].'</a>
                        <i class="ms-2 bi bi-three-dots" onclick="folder_options(this, \''.$result['id'].'\');"></i>
                        <div class="folder-options-menu" id="folder-options-'.$result['id'].'" style="display: none;"></div>
                        </div><br>';
            }
            //href="?action=openfolder&folderid='.$result['id'].'"
    }
    }else{
        echo '<p>Las carpetas te ayudan a mantener tus obras en orden. Crea tu primera carpeta!</p>';
        };
    echo '</div>
    </div>';
}

function posts_list(){
    $conn = conn();
    $user_id = $_SESSION['id_user'];
    $base_path = '<a href="?action=dash">Escritorio</a> <i class="bi bi-chevron-double-right" style="font-size:10px!important;"></i>';
    if(!isset($_GET['action']) || $_GET['action']=='dash'){
        $location = $base_path." Todos los escritos";
        //Si estamos en el escritorio, recuperamos todos los escritos
        $getPosts = jrMysqli("SELECT * FROM posts WHERE id_owner=? ORDER BY date_created DESC", $user_id);
        // $result_escritos = mysqli_fetch_all($consulta_escritos);
    }else if($_GET['action']=="openfolder"){
        //si estamos dentro de una carpeta, recuperamos los escritos que estan dentro de esa carpeta
        $folderid = $_GET['folderid'];
        $getPosts = jrMysqli("SELECT * FROM posts WHERE id_folder=? ORDER BY date_created DESC", $folderid);
        $folder_name = getFolderName($folderid);
        //var_dump($folder_name);
        $location = $base_path.' <i class="bi bi-folder"></i> '.$folder_name;
    }
    echo '
    <div class="col-lg-9 col-md-9 pt-1">
        <div class="d-flex ps-2 pb-1 border-bottom">
            <p class="dash_location">'.$location.'</p>
        </div>
        <div class="list-group" id="dash_posts_container">';

            if($getPosts){
                echo '<table class="table table-hover jr-dash-table-posts-list">
                <thead>
                    <tr>
                        <th><input type="checkbox" onchange="check(\'all\', this)"/></th>
                        <th>Título</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Carpeta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>';
                if(isMultidimensional($getPosts)===true){
                    foreach($getPosts as $post){
                        if($post['id_folder']!=="0000000000"){
                            $consulta_carpeta = jrMysqli("SELECT folder_name FROM folders WHERE id=?", $post['id_folder']);
                            $folder = $consulta_carpeta;
                        }else{
                            $folder = '...';
                        }
                            $status_init=0;
                            $status = ($post['status']==$status_init) ? 'Draft' : 'Published';
                            echo    '<tr>
                                            <td><input type="checkbox" class="post_checkbox" id="checkbox-'.$post['id'].'" onchange="check(\'this\', this)"></td>
                                            <td class="jr-dash-posts-list-title"><a href="?action=readpost&idpost='.$post['id'].'" style="text-decoration:none!important;">'.$post['title'].'</a></td>
                                            <td class="jr-dash-posts-list-meta">'.date("d/m/Y", strtotime($post['date_created'])).'</td>
                                            <td class="jr-dash-posts-list-meta">'.$status.'</td>
                                            <td class="jr-dash-posts-list-meta">'.$folder.'</td>
                                            <td>
                                                <a href="?action=editpost&idpost='.$post['id'].'"><i class="bi bi-pencil me-3 jr-list-icon"></i></a>
                                                <a href="#" onclick="deletePost('.$post.')"><i class="bi bi-trash me-3 jr-list-icon"></i></a>
                                            </td>
                                        </tr>';
                    }
                }else{
                    if($getPosts['id_folder']!=="0000000000"){
                        $getFolder = jrMysqli("SELECT folder_name FROM folders WHERE id=?", $getPosts['id_folder']);
                        $folder = $getFolder;
                    }else{
                        $folder = '...';
                    }
                        $status_init=0;
                        $status = ($getPosts['status']==$status_init) ? 'Draft' : 'Published';
                        echo    '<tr>
                                        <td class="jr-dash-posts-list-title">'.$getPosts['title'].'</td>
                                        <td class="jr-dash-posts-list-meta">'.date("d/m/Y", strtotime($getPosts['date_created'])).'</td>
                                        <td class="jr-dash-posts-list-meta">'.$status.'</td>
                                        <td class="jr-dash-posts-list-meta">'.$folder.'</td>
                                        <td>
                                            <a href="?action=editpost&idpost='.$getPosts['id'].'"><i class="bi bi-pencil me-3 jr-list-icon"></i></a>
                                            <a href="#"><i class="bi bi-trash me-3 jr-list-icon"></i></a>
                                        </td>
                                    </tr>';
                    }
                }else{
                    echo '<div class="container p-2"><p>Esta carpeta está vacía.</p></div>';
                }
                echo '
                

                </tbody>
            </table>            
        </div>
    </div>';
} 

function the_wall(){
    $conn = conn();
    $user_id = $_SESSION['id_user'];
    $posts = jrMysqli("SELECT * FROM posts WHERE status='p' ORDER BY date_created DESC");

    foreach($posts as $post){
        $autor = getAutorInfo($post['id_owner']);
        $post_id = $post['id'];
        $date = date("d M Y", strtotime($post['date_created']));
        $subcontent = substr($post['content'], 0, 220);
        $comments = jrMysqli("SELECT c.content, c.date_created, u.first_name, u.last_name 
                      FROM comments c 
                      JOIN users u ON c.id_user = u.id 
                      WHERE c.id_post = ? 
                      ORDER BY c.date_created DESC LIMIT 2", $post_id);
        //Reiniciar variables
        $votes = 0;
        $voted = '<i class="bi bi-hand-thumbs-up me-1 voted-grey" onclick="vote(\''.$post_id.'\')"></i>';
        //Consultar votos del post
        $consult_votes = jrMysqli("SELECT * FROM votes WHERE id_post=?", $post_id);
        if($consult_votes){
            $votes = $consult_votes['votes'];
            //Consulto si el usuario ha votado
            $consult_user_voted = jrMysqli("SELECT * FROM votes_users WHERE id_owner=? && id_post=?", $user_id, $post_id);
            if($consult_user_voted){
                $voted = '<i class="bi bi-hand-thumbs-up-fill me-1 voted-colored" onclick="vote(\''.$post_id.'\')"></i>';
            }
        }

        echo '<div class="container pt-2 pb-2">
                <div>
                    <div class="m-p-cont rounded bg-light p-2">
                        <div class="m-p-header border-bottom d-flex pb-1 justify-content-between align-top">
                            <!-- Cabecera del post -->
                            <div class="d-flex">
                                <div class="m-p-user-img rounded-circle me-2">
                                    <img src="img/users/jose.jpg"/>
                                </div>
                                <div class="m-p-meta">
                                    <p class="m-p-user-name">'.$autor['first_name'].' '.$autor['last_name'].'</p>
                                    <p class="m-p-post-date">'.$date.'</p>
                                </div>
                            </div>
                        </div>
                        <!-- Cuerpo del post -->
                        <div class="m-p-body pt-2 pb-2 jr-post-body" onclick="seePost(\''.$post['id'].'\')">
                            <div class="m-p-content">
                                <p class="m-p-post-title">'.$post['title'].'</p>
                                <p class="m-p-post-content">'.$subcontent.' <span>...</span></p>
                            </div>
                        </div>
                        <div class="m-p-footer border-top d-flex pt-1">
                             <div class="m-p-actions-container d-flex">
                                 <div class="d-flex align-items-center me-3">
                                     '.$voted.'
                                     <span class="votes_quantity">'.$votes.'</span>
                                 </div>
                                 <div class="d-flex">
                                     <i class="bi bi-bookmark-heart me-2"></i>
                                 </div> 
                             </div>
                         </div>
                        <!-- Lista de comentarios -->
                        <div class="m-p-comments pt-1 pb-1 border-top">
                            <form onsubmit="addComment(event, \''.$post_id.'\')">
                                <input class="form-control post-comment-input" type="text" name="comentario" placeholder="Comentar">
                            </form>
                            <div class="comments-list" id="comments_'.$post_id.'">';
                            if (is_array($comments)) {
                                if (isMultidimensional($comments)) {
                                    // Si es un array multidimensional, recorremos cada comentario
                                    foreach ($comments as $comment) {
                                        if (!empty($comment['content'])) { // Verificar si el comentario tiene contenido
                                            $userImg = "img/users/jose.jpg";
                                            echo '<div class="comment-item d-flex justify-contents-between">
                                                    <div class="me-2 wall-post-comments-user-img-box" style="background-image: url('.$userImg.');width:35px;height:35px;background-size:cover;overflow:hidden;border-radius:18px;"></div>
                                                    <div>
                                                        <p><strong>'.$comment['first_name'].' '.$comment['last_name'].'</strong></p>
                                                        <p>'.$comment['content'].'</p>
                                                    </div>
                                                  </div>';
                                        }
                                    }
                                } else {
                                    // Si no es multidimensional, significa que hay solo un comentario
                                    if (!empty($comments['content'])) { // Verificar si el comentario tiene contenido
                                        $userImg = "img/users/jose.jpg";
                                        echo '<div class="comment-item d-flex justify-contents-between">
                                                <div class="me-2 wall-post-comments-user-img-box" style="background-image: url('.$userImg.');width:35px;height:35px;background-size:cover;overflow:hidden;border-radius:18px;"></div>
                                                <div>
                                                    <p><strong>'.$comments['first_name'].' '.$comments['last_name'].'</strong></p>
                                                    <p>'.$comments['content'].'</p>
                                                </div>
                                              </div>';
                                    }
                                }
                            }                            
                            
        echo '              </div>
                        </div>
                    </div>
                </div>
            </div>';
    }
}


function getPostInfo($id_post){
    $conn = conn();
    $datos = jrMysqli("SELECT * FROM posts WHERE id=?", $id_post);
    return $datos;
}

function getAutorInfo($id){
    $conn = conn();
    $datos = jrMysqli("SELECT * FROM users WHERE id=?", $id);
    return $datos;
}

function getPostVotes($postId){
    $conn = conn();
    $datos = jrMysqli("SELECT * FROM votes WHERE id_post=?", $postId);
    return $datos;
}