function load(uid) {
    $.get(
        'index.php',
        {
            do:  'load',
            uid: uid
        },
        function(data){
            $('#paste').html(PR.prettyPrintOne(data,null, true)).addClass('prettyprint');
            $('#newpaste').html(data);
            loadcomments(uid);
        }

    );
}

function loadcomments(uid){
    $.get(
        'index.php',
        {
            do: 'loadcomments',
            uid: uid
        },
        function(data){

            var $lines = $('#paste').find('li');

            for(var i=0; i<data.length; i++) {
                commentshow($($lines.get(data[i].line)), data[i].comment);
            }

            $lines.click(function(e){
                if(e.target != this) return;
                commentform(uid, $(this));
                e.preventDefault();
                e.stopPropagation();
            });
        }
    )
}

function commentshow($li, comment) {
    var $last = $li.children().last();

    var $comment = $('<div class="comment"></div>');
    $comment.text(comment);

    if($last.hasClass('newcomment')) {
        $last.before($comment);
    } else {
        $last.after($comment);
    }
}

function commentsave(uid, $form) {
    var $txtarea = $form.find('textarea');
    var comment = $txtarea.val();
    if(!comment.length) return;
    $form.toggle();

    $.post(
        'index.php',
        {
            do: 'savecomment',
            uid: uid,
            comment: comment,
            line: $form.parent().index()
        },
        function(data) {
            if(!data) {
                alert('Something went wrong');
                return;
            }
            $txtarea.val('');
            commentshow($($form.parent()), comment);
        }
    )
}

function commentform(uid, $li) {
    var $form = $li.find('.newcomment');
    if(!$form.length) {
        $form = $('<div class="newcomment"><textarea></textarea><button>Save</button></div>');
        $form.find('button').click(function(e){
            commentsave(uid, $form);
            e.preventDefault();
            e.stopPropagation();
        });

        $li.append($form);
    } else {
        $form.toggle();
    }
}


function save() {
    var $textarea = $('#newpaste');
    var content = $textarea.val();
    if(!content.length) return;

    $.post(
        'index.php',
        {
            do: 'save',
            'content': content
        },
        function(data) {
            if(data === false) {
                alert('something went wrong');
                return;
            }
            $textarea.val('');
            location = location.pathname+'#'+data;
            location.reload()
        }
    )
}

$(function(){

    $('#new').find('button').click(function(e){
        save();
        e.preventDefault();
        e.stopPropagation();
    });


    if(location.hash.length) {
        load(location.hash.substr(1));
    }


});