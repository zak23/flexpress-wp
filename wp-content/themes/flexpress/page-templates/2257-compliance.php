<?php
/**
 * Template Name: 2257 Compliance
 */

get_header();
?>

<div class="site-main legal-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-3"><?php the_title(); ?></h1>
                        </div>

                        <div class="content">
                            <?php the_content(); ?>

                            <?php if(empty(get_the_content())): ?>
                            <div class="compliance-info">
                                <h2 class="h5 mb-3">18 U.S.C. 2257 Compliance Notice</h2>
                                
                                <p>All models, actors, actresses and other persons who appear in any visual depiction of sexually explicit conduct appearing or otherwise contained on this website were over the age of eighteen (18) years at the time of the creation of such depictions.</p>
                                
                                <p>In compliance with the Federal Labeling and Record-Keeping Law (18 U.S.C. 2257), all records required to be maintained by federal law are in the possession of the appropriate Records Custodian.</p>
                                
                                <h3 class="h6 mt-4 mb-2">Records Custodian</h3>
                                <p>
                                    [YOUR COMPANY NAME]<br>
                                    [STREET ADDRESS]<br>
                                    [CITY, STATE, ZIP]<br>
                                    [COUNTRY]
                                </p>
                                
                                <h3 class="h6 mt-4 mb-2">Exemption Statement</h3>
                                <p>The owners and operators of this website are not the primary producer (as that term is defined in 18 U.S.C. § 2257) of any of the visual content contained in this website.</p>
                                
                                <h3 class="h6 mt-4 mb-2">Producer Statement</h3>
                                <p>For content subject to 18 U.S.C. § 2257 that is produced by third parties and for which the owners and operators of this website serve as a distributor, the owners and operators of this website certify that the Records Custodian information listed above is accurate with respect to content produced by the owners and operators of this website. For content produced by third parties, the relevant records are either maintained by the third-party producer or by the custodian of records as noted in the relevant section of the website.</p>
                                
                                <h3 class="h6 mt-4 mb-2">Designated Age-Verification/Records Compliance Date</h3>
                                <p>This website follows a strict zero-tolerance policy against illegal pornography. All models, actors, and actresses are at least 18 years of age. Proof of age is held on file in accordance with U.S.C. 18 § 2257 compliance requirements.</p>
                                
                                <h3 class="h6 mt-4 mb-2">Final Statement</h3>
                                <p>This statement outlines the compliance of this website with 18 U.S.C. § 2257 and 18 U.S.C. § 2257A. This website prohibits the uploading of any illegal material, and any illegal content uploaded by users will be reported to the appropriate law enforcement agencies.</p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php 
                        // Display additional content if set
                        flexpress_display_legal_additional_content(); 
                        ?>

                        <?php 
                        // Display contact form if configured
                        flexpress_display_legal_contact_form(); 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
get_footer(); 