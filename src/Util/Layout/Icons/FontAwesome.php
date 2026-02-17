<?php

/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for styling data as HTML
 */

declare(strict_types=1);

namespace Saf\Util\Layout\Icons;


class FontAwesome
{ // map Font Awesome icons https://fontawesome.com/
        
        // 'address-book'; // fa 5 free
        // 'address-card'; // fa 5 free
        // 'adjust'; // fa 5 free
        // 'align-left'; // fa 5 free
        // 'american-sign-language'; // fa 5 free
        // 'anchor'; // fa 5 free
        // 'apple-alt'; // fa 5 free
        // 'archive'; // fa 5 free
        // 'archway'; // fa 5 free
        // 'arrow-alt-circle-down'; // fa 5 free
        // 'arrow-alt-circle-left'; // fa 5 free
        // 'arrow-alt-circle-right'; // fa 5 free
        // 'arrow-alt-circle-up'; // fa 5 free
        // 'arrows-alt'; // fa 5 free
        // 'arrows-alt-h'; // fa 5 free
        // 'arrows-alt-v'; // fa 5 free
        // 'asterisk'; // fa 5 free
        // 'at'; // fa 5 free
        // 'atlas'; // fa 5 free
        // 'atom'; // fa 5 free
        // 'audio-description'; // fa 5 free
        // 'award'; // fa 5 free
        // 'backspace'; // fa 5 free
        // 'backward'; // fa 5 free
        // 'bacteria'; // fa 5 free
        // 'balance-scale'; // fa 5 free
        // 'bell'; // fa 5 free
        // 'bell-slash'; // fa 5 free
        // 'bolt'; // fa 5 free
        // 'book'; // fa 5 free
        // 'book-open'; // fa 5 free
        // 'bookmark'; // fa 5 free
        // 'border-all'; // fa 5 free
        // 'border-style'; // fa 5 free
        // 'box'; // fa 5 free
        // 'boxes'; // fa 5 free
        // 'brain'; // fa 5 free
        public const string BUG = 'bug'; // fa 5 free
        // 'building'; // fa 5 free
        // 'burn'; // fa 5 free
        // 'calculator'; // fa 5 free
        // 'calendar-alt'; // fa 5 free
        // 'camera'; // fa 5 free
        // 'campground'; // fa 5 free
        // 'capsules'; // fa 5 free
        // 'car'; // fa 5 free
        // 'caret-square-down'; // fa 5 free
        // 'caret-square-left'; // fa 5 free
        // 'caret-square-right'; // fa 5 free
        // 'caret-square-up'; // fa 5 free
        // 'carrot'; // fa 5 free
        // 'certificate'; // fa 5 free
        // 'chalkboard-teacher'; // fa 5 free
        // 'chart-area'; // fa 5 free
        // 'chart-bar'; // fa 5 free
        // 'chart-line'; // fa 5 free
        // 'chart-pie'; // fa 5 free
        public const string CHECK_CIRCLE = 'check-circle'; // fa 5 free
        // 'check-square'; // fa 5 free
        // 'cheese'; // fa 5 free
        // 'chess'; // fa 5 free
        // 'chess-knight'; // fa 5 free
        // 'chevron-circle-down'; // fa 5 free
        // 'chevron-circle-left'; // fa 5 free
        // 'chevron-circle-right'; // fa 5 free
        // 'chevron-circle-up'; // fa 5 free
        // 'child'; // fa 5 free
        // 'circle'; // fa 5 free
        // 'circle-notch'; // fa 5 free
        // 'city'; // fa 5 free
        // 'clipboard'; // fa 5 free
        // 'clipboard-check'; // fa 5 free
        // 'clipboard-list'; // fa 5 free
        // 'clock;' // fa 5 free
        // 'clone'; // fa 5 free
        // 'closed-captioning'; // fa 5 free
        // 'cloud'; // fa 5 free
        // 'cloud-download-alt;' // fa 5 free
        // 'cloud-meatball'; // fa 5 free
        // 'cloud-moon'; // fa 5 free
        // 'cloud-heavy-showers'; // fa 5 free
        // 'cloud-sun'; // fa 5 free
        // 'cloud-upload-alt'; // fa 5 free
        // 'cocktail'; // fa 5 free
        // 'code'; // fa 5 free
        // 'code-branch'; // fa 5 free
        // 'cog'; // fa 5 free
        // 'cogs'; // fa 5 free
        // 'coins'; // fa 5 free
        // 'columns'; // fa 5 free
        // 'comment'; // fa 5 free
        // 'comment-alt'; // fa 5 free
        // 'comment-dots'; // fa 5 free
        // 'comments'; // fa 5 free
        // 'compass'; // fa 5 free
        // 'compress'; // fa 5 free
        // 'cookie-bite'; // fa 5 free
        // 'copy'; // fa 5 free
        // 'crop'; // fa 5 free
        // 'crown'; // fa 5 free
        // 'cube'; // fa 5 free
        // 'cubes'; // fa 5 free
        // 'cut'; // fa 5 free
        public const string DATABASE = 'database'; // fa 5 free
        // 'deaf'; // fa 5 free
        // 'desktop'; // fa 5 free
        // 'dice'; // fa 5 free
        // 'directions'; // fa 5 free
        // 'dna'; // fa 5 free
        // 'door-open'; // fa 5 free
        // 'dit-circle'; // fa 5 free
        // 'download'; // fa 5 free
        // 'drafting-compass'; // fa 5 free
        // 'draw-polygon'; // fa 5 free
        // 'dumpsterfire'; // fa 5 free
        // 'dungeon'; // fa 5 free
        // 'edit'; // fa 5 free
        // 'egg'; // fa 5 free
        // 'eject'; // fa 5 free
        // 'ellipsis'; // fa 5 free
        // 'ellipsis-v'; // fa 5 free
        // 'envelope-open-text'; // fa 5 free
        // 'envelope-square'; // fa 5 free
        // 'equals'; // fa 5 free
        // 'eraser'; // fa 5 free
        // 'exclamation-circle'; // fa 5 free
        public const string ERROR = 'exclamation-triangle'; // fa 5 free
        // 'expand'; // fa 5 free
        // 'external-link-alt'; // fa 5 free
        // 'eye'; // fa 5 free
        // 'eye-slash'; // fa 5 free
        // 'fast-backward'; // fa 5 free
        // 'fast-forward'; // fa 5 free
        // 'feather-alt'; // fa 5 free
        // 'file'; // fa 5 free
        // 'file-alt'; // fa 5 free
        // 'file-archive'; // fa 5 free
        // 'file-audio'; // fa 5 free
        // 'file-code'; // fa 5 free
        // 'file-export'; // fa 5 free
        // 'file-invoice'; // fa 5 free
        // 'file-image'; // fa 5 free
        // 'file-import'; // fa 5 free
        // 'filter'; // fa 5 free
        // 'fingerprint'; // fa 5 free
        // 'fire-alt'; // fa 5 free
        // 'flag'; // fa 5 free
        // 'flask'; // fa 5 free
        // 'folder-open'; // fa 5 free
        // 'forward'; // fa 5 free
        // 'gamepad'; // fa 5 free
        // 'globe-americas'; // fa 5 free
        // 'guitar'; // fa 5 free
        // 'h-square'; // fa 5 free
        // 'hammer'; // fa 5 free
        // 'hand-point-right'; // fa 5 free
        // 'handshake'; // fa 5 free
        // 'hashtag'; // fa 5 free
        // 'headphones'; // fa 5 free
        // 'heart'; // fa 5 free
        // 'highlither'; // fa 5 free
        // 'hiking'; // fa 5 free
        // 'history'; // fa 5 free
        // 'home';  // fa 5 free
        // 'horse-head'; // fa 5 free
        // 'hourglass'; // fa 5 free
        // 'icons'; // fa 5 free
        // 'id-card'; // fa 5 free
        // 'image'; // fa 5 free
        // 'inbox'; // fa 5 free
        // 'industry'; // fa 5 free
        public const string INFO_CIRCLE = 'info-circle'; // fa 5 free
        // 'key'; // fa 5 free
        // 'keyboard'; // fa 5 free
        // 'landmark'; // fa 5 free
        // 'laptop'; // fa 5 free
        // 'laptop-code'; // fa 5 free
        // 'layer-group'; // fa 5 free
        // 'leaf'; // fa 5 free
        // 'list-alt'; // fa 5 free
        // 'lock'; // fa 5 free
        // 'lock-open'; // fa 5 free
        // 'magic'; // fa 5 free
        // 'map-marker-alt'; // fa 5 free
        // 'map-signs'; // fa 5 free
        // 'meteor'; // fa 5 free
        // 'microphone'; // fa 5 free
        // 'microchip'; // fa 5 free
        // 'microscope'; // fa 5 free
        // 'minus-circle'; // fa 5 free
        // 'monument'; // fa 5 free
        // 'moon'; // fa 5 free
        // 'network-wired'; // fa 5 free
        // 'not-equal'; // fa 5 free
        // 'palette'; // fa 5 free
        // 'paperclip'; // fa 5 free
        // 'paragraph'; // fa 5 free
        // 'paste'; // fa 5 free
        // 'pause-circle'; // fa 5 free
        // 'pen-square'; // fa 5 free
        // 'photo-video'; // fa 5 free
        // 'poll'; // fa 5 free
        // 'play-circle'; // fa 5 free
        // 'plus-circle'; // fa 5 free
        // 'power-off'; // fa 5 free
        // 'puzzle-peice'; // fa 5 free
        // 'qr-code'; // fa 5 free
        public const string QUESTION_CIRCLE = 'question-circle'; // fa 5 free
        // 'redo-alt'; // fa 5 free
        // 'reply'; // fa 5 free
        // 'search'; // fa 5 free
        // 'seedling'; // fa 5 free
        // 'server'; // fa 5 free
        // 'share-alt-square'; // fa 5 free
        // 'site-map'; // fa 5 free
        // 'snowflake'; // fa 5 free
        // 'sort'; // fa 5 free
        // 'sort-down'; // fa 5 free
        // 'sort-up'; // fa 5 free
        // 'spinner'; // fa 5 free
        // 'square'; // fa 5 free
        // 'star'; // fa 5 free
        // 'sticky-note'; // fa 5 free
        // 'stop-circle'; // fa 5 free
        // 'stopwatch'; // fa 5 free
        // 'sun'; // fa 5 free
        // 'suprise'; // fa 5 free
        // 'table'; // fa 5 free
        // 'tachometer-alt'; // fa 5 free
        // 'tag'; // fa 5 free
        // 'tags'; // fa 5 free
        // 'tasks'; // fa 5 free
        // 'terminal'; // fa 5 free
        // 'thermometer-half'; // fa 5 free
        public const string TIMES_CIRCLE = 'times-circle'; // fa 5 free
        // 'tools'; // fa 5 free
        // 'trash'; // fa 5 free
        // 'trash-restore'; // fa 5 free
        // 'tree'; // fa 5 free
        // 'trophy'; // fa 5 free
        // 'tv'; // fa 5 free
        // 'undo-alt'; // fa 5 free
        // 'unlock'; // fa 5 free
        // 'upload'; // fa 5 free
        // 'user'; // fa 5 free
        // 'user-check'; // fa 5 free
        // 'user-clock'; // fa 5 free
        // 'user-cog'; // fa 5 free
        // 'user-edit'; // fa 5 free
        // 'users'; // fa 5 free

}