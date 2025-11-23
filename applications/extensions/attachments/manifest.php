<?php

global $TANGO;

// Inject file input into the Attachments tab (%misc% placeholder in thread_options entity)
$attachment_input = '<div class="form-group">';
$attachment_input .= '<label for="attachment">Upload File</label>';
$attachment_input .= '<input type="file" name="attachment" id="attachment" class="form-control" />';
$attachment_input .= '</div>';
$attachment_input .= '<script>
document.addEventListener("DOMContentLoaded", function() {
    var form = document.getElementById("tango_form");
    if(form) {
        form.setAttribute("enctype", "multipart/form-data");
    }
});
</script>';

// We use addEntParam because new.php uses the thread_options entity which has the %misc% placeholder.
// thread_options is called without parameters in new.php, so it falls back to ent_params.
$TANGO->tpl->addEntParam('misc', $attachment_input);


// Hook to handle file upload after thread creation
$TANGO->hook->add_action('after_thread_create', function($thread_id) {
    global $MYSQL;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'public/uploads/attachments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
            // Create .htaccess to prevent script execution
            file_put_contents($upload_dir . '.htaccess', "RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phps\n<FilesMatch \"\.(php|phtml|php3|php4|php5|php7|phps)$\">\n    Order Allow,Deny\n    Deny from all\n</FilesMatch>");
        }

        $tmp_name = $_FILES['attachment']['tmp_name'];
        $original_name = $_FILES['attachment']['name'];
        $size = $_FILES['attachment']['size'];
        $type = $_FILES['attachment']['type'];

        // Generate a unique filename
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        // Block executable extensions
        $blocked_extensions = array('php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phps', 'phar', 'exe', 'sh', 'bat', 'cmd');
        if (in_array($extension, $blocked_extensions)) {
            return; // Silently fail or handle error
        }

        $filename = uniqid() . '.' . $extension;
        $destination = $upload_dir . $filename;

        if (move_uploaded_file($tmp_name, $destination)) {
            $time = time();
            $MYSQL->bindMore(array(
                'thread_id' => $thread_id,
                'filename' => $filename,
                'original_filename' => $original_name,
                'file_size' => $size,
                'file_type' => $type,
                'uploaded_at' => $time
            ));

            try {
                $MYSQL->query("INSERT INTO {prefix}forum_attachments (thread_id, filename, original_filename, file_size, file_type, uploaded_at) VALUES (:thread_id, :filename, :original_filename, :file_size, :file_type, :uploaded_at)");
            } catch (Exception $e) {
                // Handle error if needed, but we don't want to break the redirect
            }
        }
    }
});

// Filter to display attachments in the thread
$TANGO->hook->add_filter('thread_content', function($content, $thread_id) {
    global $MYSQL;

    $MYSQL->bind('thread_id', $thread_id);
    $attachments = $MYSQL->query("SELECT * FROM {prefix}forum_attachments WHERE thread_id = :thread_id");

    if (!empty($attachments)) {
        $content .= '<br /><br /><hr /><h4>Attachments</h4><ul>';
        foreach ($attachments as $att) {
            $file_url = SITE_URL . '/public/uploads/attachments/' . $att['filename'];
            $content .= '<li><a href="' . $file_url . '" target="_blank">' . htmlspecialchars($att['original_filename']) . '</a> (' . bytesToSize($att['file_size']) . ')</li>';
        }
        $content .= '</ul>';
    }

    return $content;
});

?>
