/**
 * Cheeky ACF Repeater Mod JavaScript
 * Adds repeater-like functionality using regular fields and JavaScript
 */

jQuery(document).ready(function ($) {
  "use strict";

  // FAQ Repeater functionality
  $(".flexpress-repeater").each(function () {
    var $repeater = $(this);
    var fieldName = $repeater.data("field-name");
    var $items = $repeater.find(".repeater-items");
    var $addButton = $repeater.find(".add-repeater-item");

    // Add new item
    $addButton.on("click", function () {
      var index = $items.find(".repeater-item").length;
      var itemHtml = "";

      if (fieldName === "flexpress_faq_items") {
        itemHtml = getFaqItemHtml(index);
      } else if (fieldName === "flexpress_requirements_items") {
        itemHtml = getRequirementsItemHtml(index);
      }

      $items.append(itemHtml);
      updateItemNumbers();
    });

    // Remove item
    $items.on("click", ".remove-repeater-item", function () {
      $(this).closest(".repeater-item").remove();
      updateItemNumbers();
    });

    // Update item numbers
    function updateItemNumbers() {
      $items.find(".repeater-item").each(function (index) {
        var $item = $(this);
        $item.attr("data-index", index);
        $item
          .find(".repeater-item-title")
          .text(
            fieldName === "flexpress_faq_items"
              ? "FAQ Item " + (index + 1)
              : "Requirement Card " + (index + 1)
          );

        // Update input names
        $item.find("input, textarea").each(function () {
          var $input = $(this);
          var name = $input.attr("name");
          if (name) {
            var newName = name.replace(/\[\d+\]/, "[" + index + "]");
            $input.attr("name", newName);
          }
        });
      });
    }

    // Initialize with default items if empty
    if ($items.find(".repeater-item").length === 0) {
      if (fieldName === "flexpress_faq_items") {
        // Add default FAQ items
        for (var i = 0; i < 8; i++) {
          $items.append(getFaqItemHtml(i));
        }
      } else if (fieldName === "flexpress_requirements_items") {
        // Add default requirement cards
        for (var i = 0; i < 3; i++) {
          $items.append(getRequirementsItemHtml(i));
        }
      }
      updateItemNumbers();
    }
  });

  function getFaqItemHtml(index) {
    var faqData = [
      {
        question: "How long does a typical shoot day last?",
        answer:
          "Shoot days typically last 6-8 hours, including breaks, hair, and makeup time. We ensure regular breaks and maintain a comfortable, professional environment throughout the day.",
        expanded: true,
      },
      {
        question: "What should I bring to a shoot?",
        answer:
          "While we provide professional hair, makeup, and wardrobe options, you're welcome to bring your favorite outfits or accessories. We recommend bringing comfortable clothes to wear between scenes and any personal items you might need throughout the day.",
        expanded: false,
      },
      {
        question: "How quickly will I hear back after applying?",
        answer:
          "We typically respond to all applications within 2-3 business days. If selected, we'll schedule an initial video call to discuss opportunities and answer any questions you might have.",
        expanded: false,
      },
      {
        question: "Do you provide transportation?",
        answer:
          "While we don't provide regular transportation, we can assist with travel arrangements for shoots and may cover travel expenses for certain productions. This is discussed on a case-by-case basis.",
        expanded: false,
      },
      {
        question: "What about privacy and discretion?",
        answer:
          "We take privacy very seriously. All shoots are conducted in secure, private locations. Your personal information is kept strictly confidential, and we offer flexible content agreements regarding distribution and marketing.",
        expanded: false,
      },
      {
        question: "Do you accept newcomers?",
        answer:
          "Yes! We welcome both experienced performers and newcomers. Our professional team provides guidance and support throughout the process, ensuring everyone feels comfortable and confident on set.",
        expanded: false,
      },
      {
        question: "What about health and safety?",
        answer:
          "Health and safety are our top priorities. We require recent health certificates and maintain strict hygiene protocols on set. Our team follows industry-standard safety practices, and we provide a clean, professional environment for all shoots.",
        expanded: false,
      },
      {
        question: "What kind of content do you produce?",
        answer:
          "We produce high-quality adult content with a focus on professionalism and creativity. During the application process, we'll discuss the types of content you're comfortable with and ensure all boundaries are respected.",
        expanded: false,
      },
    ];

    var item = faqData[index] || { question: "", answer: "", expanded: false };

    return (
      '<div class="repeater-item" data-index="' +
      index +
      '">' +
      '<div class="repeater-item-header">' +
      '<span class="repeater-item-title">FAQ Item ' +
      (index + 1) +
      "</span>" +
      '<button type="button" class="button remove-repeater-item">Remove</button>' +
      "</div>" +
      '<div class="repeater-item-content">' +
      "<p>" +
      "<label>Question:</label><br>" +
      '<input type="text" name="flexpress_faq_items[' +
      index +
      '][question]" value="' +
      item.question +
      '" class="widefat">' +
      "</p>" +
      "<p>" +
      "<label>Answer:</label><br>" +
      '<textarea name="flexpress_faq_items[' +
      index +
      '][answer]" class="widefat" rows="4">' +
      item.answer +
      "</textarea>" +
      "</p>" +
      "<p>" +
      "<label>" +
      '<input type="checkbox" name="flexpress_faq_items[' +
      index +
      '][expanded]" value="1"' +
      (item.expanded ? " checked" : "") +
      ">" +
      "Expanded by default" +
      "</label>" +
      "</p>" +
      "</div>" +
      "</div>"
    );
  }

  function getRequirementsItemHtml(index) {
    var reqData = [
      {
        icon_class: "fas fa-id-card",
        title: "Legal Requirements",
        requirements: [
          "Must be 18+ years old",
          "Valid government ID",
          "Right to work in Australia",
        ],
      },
      {
        icon_class: "fas fa-clipboard-check",
        title: "Health & Safety",
        requirements: [
          "Recent health certificates",
          "Professional attitude",
          "Reliable transportation",
        ],
      },
      {
        icon_class: "fas fa-star",
        title: "Personal Qualities",
        requirements: [
          "Positive attitude",
          "Reliable and punctual",
          "Team player mindset",
        ],
      },
    ];

    var item = reqData[index] || {
      icon_class: "",
      title: "",
      requirements: [],
    };

    return (
      '<div class="repeater-item" data-index="' +
      index +
      '">' +
      '<div class="repeater-item-header">' +
      '<span class="repeater-item-title">Requirement Card ' +
      (index + 1) +
      "</span>" +
      '<button type="button" class="button remove-repeater-item">Remove</button>' +
      "</div>" +
      '<div class="repeater-item-content">' +
      "<p>" +
      "<label>Icon Class:</label><br>" +
      '<input type="text" name="flexpress_requirements_items[' +
      index +
      '][icon_class]" value="' +
      item.icon_class +
      '" class="widefat" placeholder="fas fa-star">' +
      "</p>" +
      "<p>" +
      "<label>Title:</label><br>" +
      '<input type="text" name="flexpress_requirements_items[' +
      index +
      '][title]" value="' +
      item.title +
      '" class="widefat">' +
      "</p>" +
      "<p>" +
      "<label>Requirements (one per line):</label><br>" +
      '<textarea name="flexpress_requirements_items[' +
      index +
      '][requirements]" class="widefat" rows="4" placeholder="Must be 18+ years old&#10;Valid government ID&#10;Right to work in Australia">' +
      item.requirements.join("\n") +
      "</textarea>" +
      "</p>" +
      "</div>" +
      "</div>"
    );
  }
});
