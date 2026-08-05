<?php

return [

    'brand' => 'MEMO STORE',

    'nav' => [
        'videos' => 'Videos',
        'contact' => 'Contact us',
    ],

    'hero' => [
        'title' => 'See our work for yourself.',
        'title_em' => "And confirm it's really ours.",
        'subtitle' => 'Explainer videos of what we offer — every video carries our watermark burned into the picture, with a verify code proving it came from us, not from any account impersonating our name.',
        'cta_browse' => 'Browse the videos',
        'cta_protect' => "How to verify it's ours",
    ],

    'library' => [
        'eyebrow' => 'Library',
        'title' => 'All videos',
        'subtitle' => 'Click any video to watch instantly — no sign-up, no subscription.',
        'empty' => 'No published videos yet.',
        'load_error' => "Couldn't load the library.",
        'tab_all' => 'All',
        'views_unit' => 'views',
    ],

    'protect' => [
        'eyebrow' => 'Anti-impersonation',
        'title' => 'How to verify a video is ours',
        'subtitle' => "Some people steal our videos and claim them as their own. Here's how to check in seconds.",
        'cards' => [
            [
                'title' => 'Watermark burned into the frame',
                'body' => "The MEMO logo and our WhatsApp number are composited into every frame's pixels. Any stolen copy still carries our mark.",
            ],
            [
                'title' => 'A verify code for every video',
                'body' => "Every video has a code underneath it. Open /verify/CODE and it'll instantly tell you: official or not.",
            ],
            [
                'title' => 'First-publish date on record',
                'body' => 'Every video is logged with its first publish date and a digital fingerprint of the original file — ready-made ownership proof for any platform.',
            ],
            [
                'title' => 'If you spot an impersonating account',
                'body' => 'Send us the link on WhatsApp. Our official channels are only the ones listed below.',
            ],
        ],
    ],

    'footer' => [
        'copyright' => '© 2026 MEMO STORE · Every video carries our watermark and a verify code',
        'channels' => [
            'whatsapp1' => 'WhatsApp 01095236175',
            'whatsapp2' => 'WhatsApp 01091349700',
            'instagram' => '@memo__store11',
            'tiktok' => 'TikTok',
        ],
    ],

    'player' => [
        'play_error' => 'Could not play the video right now.',
        'unlock_needed' => 'Finish the previous chapter to open this one.',
        'unavailable' => 'Playback is unavailable right now. Try again in a moment.',
        'stream_dropped' => 'The stream dropped. Reload to continue where you left off.',
        'stopped_title' => 'Playback stopped',
        'checkpoint_title' => 'Checkpoint — :count questions',
        'submit' => 'Submit answers',
        'answer_all' => 'Answer every question before submitting.',
        'passed' => 'Passed with :score% — chapter :chapter is open.',
        'no_attempts' => 'No attempts left. Rewatch the chapter to reset.',
        'score_left' => ':score% — :left attempts left.',
        'rewatch_from' => 'Rewatch from :marks.',
    ],

    'verify' => [
        'page_title' => 'Verify authenticity — MEMO STORE',
        'checking' => 'Verifying…',
        'unverified_badge' => '✕ Unverified',
        'unverified_title' => 'No official video with this code',
        'unverified_body' => 'The code :code is not registered with us. If you see this code on a video, that video is not from MEMO STORE.',
        'unverified_note' => 'Always check the code before dealing with any account claiming to be us.',
        'verified_badge' => '✓ Officially verified video',
        'verified_body' => 'This video is produced by :owner and officially published on our channels.',
        'row_code' => 'Verify code',
        'row_first_published' => 'First published',
        'row_duration' => 'Duration',
        'watch_original' => 'Watch the original',
        'whatsapp' => 'WhatsApp :number',
        'verified_note' => 'Our official channels are only the ones listed above. Any other account showing this video and claiming it as their own is impersonating us — report it to us via WhatsApp.',
        'check_failed_badge' => '✕ Verification failed',
        'check_failed_body' => 'Try again in a moment.',
        'all_videos_link' => 'All official videos',
    ],

    'admin' => [

        'nav' => [
            'lib' => 'Library',
            'videos' => 'Videos',
            'upload' => 'Upload',
            'brand' => 'Brand',
            'wm' => 'Logo & watermark',
            'protect' => 'Protection',
            'leaks' => 'Theft reports',
            'activity' => 'Activity',
            'cats' => 'Categories',
            'refresh' => 'Refresh',
        ],

        'videos' => [
            'h' => 'Video library',
            'title' => 'Title',
            'cat' => 'Category',
            'dur' => 'Duration',
            'views' => 'Views',
            'wm' => 'Mark',
            'code' => 'Verify code',
            'status' => 'Status',
            'empty' => 'No videos yet. Upload your first from the Upload section.',
        ],

        'upload' => [
            'h' => 'Upload a video',
            'sub' => '8 MB chunks · resumes automatically · up to 20 GB',
            'drop' => 'Drop the master file here',
            'hint' => 'MP4 / MOV / MKV — the original never leaves the private disk',
            'browse' => 'Browse files',
            't_en' => 'Title (English)',
            't_ar' => 'Title (Arabic)',
            'cat' => 'Category',
            'exp' => 'Expert',
            'desc' => 'Description',
            'start' => 'Start upload',
            'cancel' => 'Cancel',
        ],

        'brand' => [
            'h' => 'Burned-in watermark',
            'sub' => 'Composited into every frame — any stolen copy carries your mark',
            'phone' => 'Phone shown in the mark',
            'size' => 'Mark size',
            'op' => 'Opacity',
            'pos' => 'Position',
            'br' => 'Bottom right',
            'bl' => 'Bottom left',
            'tr' => 'Top right',
            'tl' => 'Top left',
            'nav' => 'Header logo',
            'hero' => 'Hero logo',
            'foot' => 'Footer logo',
            'save' => 'Save mark and logo',
            'newlogo' => 'Change logo',
            'note' => 'Applies to new uploads. To burn it into an older video, hit Reprocess in the library.',
        ],

        'leaks' => [
            'h' => 'New theft report',
            'sub' => 'Log the URL and get an evidence pack ready to send to the platform',
            'url' => 'URL of the stolen copy',
            'url2' => 'URL',
            'plat' => 'Platform',
            'who' => 'Impersonating account',
            'vid' => 'Original video',
            'add' => 'Log report',
            'list' => 'Reports',
            'orig' => 'Original',
            'empty' => 'No reports — which is good news.',
        ],

        'activity' => [
            'h' => 'Most watched (7 days)',
            'log' => 'Recent stream events',
            'live' => 'live',
        ],

        'categories' => [
            'title' => 'Categories',
            'h' => 'Manage categories',
            'name_en' => 'Name (English)',
            'name_ar' => 'Name (Arabic)',
            'add' => 'Add category',
            'empty' => 'No categories yet.',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'rename' => 'Rename',
            'videos_count' => 'Videos',
            'confirm_del' => 'Delete this category? Its videos will be detached, not deleted.',
        ],

        'edit' => [
            'title' => 'Edit video',
            'save' => 'Save changes',
            'cancel' => 'Cancel',
            'poster' => 'Thumbnail',
            'upload_poster' => 'Upload thumbnail',
            'reset_poster' => 'Reset to default',
            'verify_link' => 'Verify link',
            'desc_en' => 'Description (English)',
            'desc_ar' => 'Description (Arabic)',
        ],

    ],

    'js' => [
        'k' => [
            'total' => 'Total videos',
            'public' => 'Public',
            'proc' => 'Processing',
            'views' => 'Total views',
            'today' => 'Views today',
            'storage' => 'Storage',
            'leaks' => 'Open reports',
            'wm' => 'Watermarked',
        ],

        'st' => [
            'published' => 'Published',
            'transcoding' => 'Transcoding',
            'queued' => 'Queued',
            'uploading' => 'Uploading',
            'failed' => 'Failed',
            'draft' => 'Draft',
        ],

        'act' => [
            'publish' => 'Publish',
            'unpublish' => 'Hide',
            'retry' => 'Reprocess',
            'del' => 'Delete',
            'evi' => 'Evidence',
            'copy' => 'Copy',
            'edit' => 'Edit',
        ],

        'msg' => [
            'saved' => 'Saved',
            'pub' => 'Now public',
            'unpub' => 'Hidden',
            'del' => 'Deleted',
            'queued' => 'Queued',
            'err' => 'Something went wrong',
            'copied' => 'Copied',
            'need' => 'Add a title first',
            'lk' => 'Report logged',
        ],

        'live' => 'live',
        'videos_unit' => 'videos',
        'gb' => 'GB',
        'px' => 'px',
        'ready_hidden' => 'Ready — hidden',
        'confirm_del' => 'Delete permanently?',
        'confirm_cat_del' => 'Delete this category? Its videos will be detached, not deleted.',
        'uploading' => 'Uploading',
        'assembling' => 'Assembling…',
        'queued_tx' => 'Queued for transcode',
        'failed' => 'Transcode failed',
        'ready_wm' => 'Ready — watermarked',
        'ready' => 'Ready',
        'transcoding' => 'Transcoding',
        'drag_hint' => 'Release to upload',
    ],

];
