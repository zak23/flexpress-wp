<?php
/**
 * Template Name: Admin Guide
 * Description: Instructions for using the FlexPress theme
 */

get_header();
?>

<main id="primary" class="site-main admin-guide-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h1 class="text-center mb-5">FlexPress Admin Guide</h1>
                
                <section class="mb-5">
                    <h2>Join Page Carousel</h2>
                    <div class="card p-4 mb-4">
                        <h3>How to Add Carousel Images</h3>
                        
                        <ol class="mt-3">
                            <li>Go to <strong>Pages → All Pages</strong> in the WordPress admin</li>
                            <li>Find the <strong>Join</strong> page and click <strong>Edit</strong></li>
                            <li>Scroll down to the <strong>Join Page Carousel</strong> box (below the content editor)</li>
                            <li>You should see a form with fields for managing carousel slides</li>
                            <li>For each slide:
                                <ul>
                                    <li>Click <strong>Select Image</strong> to open the media library</li>
                                    <li>Upload or select an existing image</li>
                                    <li>Enter a heading (like "FULL LENGTH EPISODES")</li>
                                    <li>Add alt text for accessibility</li>
                                </ul>
                            </li>
                            <li>To add more slides, click the <strong>Add New Slide</strong> button</li>
                            <li>To remove a slide, click the <strong>Remove Slide</strong> button</li>
                            <li>When finished, click <strong>Update</strong> to save your changes</li>
                        </ol>
                        
                        <div class="alert alert-info mt-3">
                            <strong>Image Tips:</strong>
                            <ul class="mb-0">
                                <li>Recommended size: <strong>1920×1080 pixels</strong> (16:9 aspect ratio)</li>
                                <li>Images will maintain a perfect 16:9 ratio on all screen sizes</li>
                                <li>Use high-quality, professional images</li>
                                <li>Text overlay appears at the bottom of each slide with a semi-transparent background</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <strong>Carousel Settings:</strong>
                            <ul class="mb-0">
                                <li>You can control <strong>how long each slide displays</strong> using the "Seconds per slide" setting</li>
                                <li>The default is 5 seconds per slide</li>
                                <li>Valid range is 1-20 seconds</li>
                                <li>The total rotation time will automatically calculate based on the number of slides</li>
                            </ul>
                        </div>
                        
                        <div class="mt-4">
                            <h4>Example Result:</h4>
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/carousel-example.jpg" alt="Carousel Example" class="img-fluid border">
                        </div>
                    </div>
                </section>
                
                <!-- Add more admin guide sections here -->
                
            </div>
        </div>
    </div>
</main>

<style>
.admin-guide-page {
    background: #f8f9fa;
    color: #333;
}

.admin-guide-page h1 {
    color: #222;
    font-weight: bold;
}

.admin-guide-page h2 {
    color: #333;
    border-bottom: 2px solid #ddd;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.admin-guide-page h3 {
    font-size: 1.5rem;
    margin-bottom: 15px;
}

.admin-guide-page h4 {
    font-size: 1.2rem;
}

.admin-guide-page .card {
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    border-radius: 10px;
}

.admin-guide-page ol li,
.admin-guide-page ul li {
    margin-bottom: 8px;
}
</style>

<?php get_footer(); ?> 