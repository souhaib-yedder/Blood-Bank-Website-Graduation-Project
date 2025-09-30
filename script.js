$(document).ready(function() {
    // Smooth scrolling for internal links
    $("a.nav-link[href^=\"#\"]").on("click", function(event) {
        if (this.hash !== "") {
            event.preventDefault();
            var hash = this.hash;
            $("html, body").animate({
                scrollTop: $(hash).offset().top
            }, 800, function() {
                window.location.hash = hash;
            });
        }
    });

    // FAQ section dynamic content
    $(".faq-item").on("click", function() {
        var questionId = $(this).data("question");
        var answerDiv = $("#faq-answer");
        var answerText = "";

        // Clear previous active state and set current active
        $(".faq-item").removeClass("active");
        $(this).addClass("active");

        switch (questionId) {
            case 1:
                answerText = "نعم، التبرع بالدم آمن تمامًا ويتم تحت إشراف طبي كامل وباستخدام أدوات معقمة تستخدم لمرة واحدة فقط.";
                break;
            case 2:
                answerText = "يمكن التبرع بالدم كل 56 يومًا (حوالي شهرين) للرجال والنساء، بحد أقصى 5 مرات في السنة.";
                break;
            case 3:
                answerText = "يجب أن يكون المتبرع بصحة جيدة، وعمره بين 18 و 65 عامًا، ووزنه لا يقل عن 50 كيلوغرامًا، وأن يكون مستوى الهيموغلوبين لديه ضمن المعدل الطبيعي.";
                break;
            case 4:
                answerText = "لا، التبرع بالدم لا يؤثر سلبًا على صحتك. بل على العكس، يمكن أن يساعد في تجديد خلايا الدم وتحسين الدورة الدموية.";
                break;
            case 5:
                answerText = "تستغرق عملية التبرع بالدم حوالي 10-15 دقيقة لجمع الدم نفسه، ولكن العملية بأكملها من التسجيل والفحص إلى فترة الراحة بعد التبرع قد تستغرق حوالي ساعة.";
                break;
            case 6:
                answerText = "بعد التبرع، يُنصح بالراحة وتناول السوائل بكثرة، وتجنب الأنشطة البدنية الشاقة لبضع ساعات. سيتم تقديم وجبة خفيفة ومشروب لك في مركز التبرع.";
                break;
            default:
                answerText = "اختر سؤالاً لعرض الإجابة";
        }
        answerDiv.html("<p>" + answerText + "</p>");
    });

    // Clear FAQ answer when scrolling away
    var faqSection = $("#faq");
    var faqAnswer = $("#faq-answer");
    var originalFaqAnswerContent = faqAnswer.html();

    $(window).on("scroll", function() {
        var scrollTop = $(window).scrollTop();
        var sectionTop = faqSection.offset().top;
        var sectionBottom = sectionTop + faqSection.height();

        if (scrollTop < sectionTop - $(window).height() || scrollTop > sectionBottom) {
            // User has scrolled completely out of the FAQ section
            faqAnswer.html(originalFaqAnswerContent);
            $(".faq-item").removeClass("active");
        }
    });
});

