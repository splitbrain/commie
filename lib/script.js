var API = 'lib/router.php';

/**
 * Saves a new paste
 */
function save() {
    var $textarea = $('#newpaste');
    var content = $textarea.val();
    if(!content.length) return;

    $.post(
        API,
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

/**
 * loads as paste from backend
 *
 * @param {String} uid
 */
function load(uid) {
    $.get(
        API,
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

/**
 * Loads all current comments for the paste
 *
 * @param {String} uid
 */
function loadcomments(uid){
    $.get(
        API,
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

            $('#help').show();
        }
    )
}

/**
 * Shows the given comment at the given line
 *
 * Makes sure the comment is shown before a possible comment edit form
 *
 * @param {jQuery} $li The line element
 * @param {String} comment The comment text
 */
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

/**
 * Saves the comment that has been entered in $form
 *
 * @param {String} uid
 * @param {jQuery} $form
 */
function commentsave(uid, $form) {
    var $txtarea = $form.find('textarea');
    var comment = $txtarea.val();
    if(!comment.length) return;
    $form.toggle();

    $.post(
        API,
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

/**
 * Toggle comment form showing for the given line element
 *
 * @param {String} uid
 * @param {jQuery} $li
 */
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


/**
 * Main
 */
$(function(){
    var $new = $('#new');
    $new.find('button').click(function(e){
        save();
        e.preventDefault();
        e.stopPropagation();
    });
    $new.find('textarea').focus(function(e){
        $(this).animate({height: '35em'}, 'fast');
    });


    if(location.hash.length) {
        load(location.hash.substr(1));
    }
});