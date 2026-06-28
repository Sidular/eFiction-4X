<section class="contact-page">
    <h1><?= $this->__('contact') ?></h1>
    <?php if ($sent): ?>
        <div class="alert alert-success">Thank you for your message.</div>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= $this->e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="/contact">
            <input type="hidden" name="csrf_token" value="<?= $this->csrf() ?>">
            <label>Name <input type="text" name="name" required></label>
            <label>Email <input type="email" name="email" required></label>
            <label>Subject <input type="text" name="subject" required></label>
            <label>Message <textarea name="message" rows="6" required></textarea></label>
            <button type="submit">Send</button>
        </form>
    <?php endif; ?>
</section>
