const bodyDOM = document.querySelector("body");
let elements;
let hasThumbs = false;

document.body.addEventListener("click", async function (e) {
  if (e.target.closest("[k-box]") !== null) {
    e.preventDefault();
    e.stopPropagation();

    elements = document.querySelectorAll("[k-box]");
    await generateKBox(e.target.closest("[k-box]"));
  }
});

async function generateKBox(currentElement) {
  var elementsList = getAllElements(currentElement);
  var template = `<div class="k-box-container is-loading" id="kBoxContainer">
                    <div class="box-bg"></div>
                    <div class="box-loader"></div>
                    <div class="box-header-left"></div>
                    <div class="box-header-right">
                      <button type="button" class="box-close">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                          <path d="M12 10.6L6.6 5.2 5.2 6.6l5.4 5.4-5.4 5.4 1.4 1.4 5.4-5.4 5.4 5.4 1.4-1.4-5.4-5.4 5.4-5.4-1.4-1.4-5.4 5.4z"></path>
                        </svg>
                      </button>
                    </div>
                    <div class="box-body"></div>
                  </div>`;
  bodyDOM.classList.add("k-box-open");
  bodyDOM.insertAdjacentHTML("beforeend", template);

  const container = document.querySelector("#kBoxContainer");
  const bg = document.querySelector("#kBoxContainer .box-bg");
  const headerLeft = document.querySelector("#kBoxContainer .box-header-left");
  const body = document.querySelector("#kBoxContainer .box-body");
  const btnClose = document.querySelector("#kBoxContainer .box-close");

  // Open animation
  setTimeout(() => {
    container.classList.add("is-open");
  }, 100);

  // Get slides & captions
  let slides = await getKBoxSlides(elementsList);
  for (let i = 0; i < slides.length; i++) {
    body.insertAdjacentHTML("beforeend", slides[i]);
  }

  // Remove loader
  container.classList.remove("is-loading");
  container.classList.add("loaded");

  let boxSlides = document.querySelectorAll("#kBoxContainer .box-slide");
  boxSlides[0].classList.add("current-slide");

  // Multiple slides
  if (boxSlides.length > 1) {
    let counterTemplate = `<div class="box-counter">
                            <span class="current-slider-counter">1</span> / ${boxSlides.length}
                          </div>`;
    headerLeft.insertAdjacentHTML("beforeend", counterTemplate);

    let boxArrows = `<button type="button" class="box-arrow arrow-left disabled">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                          <path d="M11.28 15.7l-1.34 1.37L5 12l4.94-5.07 1.34 1.38-2.68 2.72H19v1.94H8.6z"></path>
                        </svg>
                      </button>
                      <button type="button" class="box-arrow arrow-right">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                          <path d="M15.4 12.97l-2.68 2.72 1.34 1.38L19 12l-4.94-5.07-1.34 1.38 2.68 2.72H5v1.94z"></path>
                        </svg>
                      </button>`;
    body.insertAdjacentHTML("beforeend", boxArrows);
    const arrows = document.querySelectorAll("#kBoxContainer .box-arrow");

    for (let b = 0; b < arrows.length; b++) {
      arrows[b].addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        changeKBoxSlide(boxSlides, arrows[b]);
      });
    }

    // Has thumbnails
    if (hasThumbs) {
      container.classList.add("has-thumbnails");
      let thumbs = await loadThumbnails(elementsList);
      let thumbsTemplate = `<div class="box-thumbnails">
                              <div class="box-thumbnails-inner">`;
      for (let t = 0; t < thumbs.length; t++) {
        thumbsTemplate += thumbs[t];
      }
      thumbsTemplate += `</div>
                        </div>`;
      container.insertAdjacentHTML("beforeend", thumbsTemplate);
    }
  }

  // Change to selected slide
  let elementIndex = 0;
  for (let s = 0; s < elementsList.length; s++) {
    if (currentElement.isSameNode(elementsList[s])) {
      elementIndex = s;
      break;
    }
  }
  changeKBoxIndex(boxSlides, elementIndex);

  // Thumbnails event
  const thumbnails = document.querySelectorAll(
    "#kBoxContainer .thumbnail-slide"
  );
  for (let t = 0; t < thumbnails.length; t++) {
    thumbnails[t].addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      changeKBoxIndex(boxSlides, thumbnails[t].getAttribute("data-index"));
    });
  }

  // Close box event
  bg.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    closeKBox(container);
  });
  btnClose.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    closeKBox(container);
  });

  // Keyboard arrows event
  document.addEventListener("keydown", function (event) {
    if (!bodyDOM.classList.contains("k-box-open")) return;

    const currentIndex = getCurrentIndex(boxSlides);
    switch (event.key) {
      case "ArrowLeft":
        changeKBoxIndex(boxSlides, currentIndex - 1);
        break;
      case "ArrowRight":
        changeKBoxIndex(boxSlides, currentIndex + 1);
        break;
      case "Escape":
        closeKBox(container);
        break;
    }
  });
}

function getAllElements(element) {
  let elementsArray = [];

  for (let i = 0; i < elements.length; i++) {
    if (elements[i].getAttribute("k-box") == element.getAttribute("k-box")) {
      if (
        element.getAttribute("k-box") == "" &&
        !element.isSameNode(elements[i])
      ) {
        continue;
      }
      if (elements[i].getAttribute("data-thumb") != null) hasThumbs = true;
      elementsArray.push(elements[i]);
    }
  }

  return elementsArray;
}

function closeKBox(box) {
  hasThumbs = false;
  box.classList.remove("is-open");
  setTimeout(() => {
    box.remove();
    bodyDOM.classList.remove("k-box-open");
  }, 300);
}

async function getKBoxSlides(elementsList) {
  let imgs = [];
  let slide = "";
  let lazyload = false;

  for (let i = 0; i < elementsList.length; i++) {
    if (i > 0 && elementsList.length > 1) lazyload = true;

    slide = await checkUrl(elementsList[i].getAttribute("href"), lazyload);
    if (elementsList[i].getAttribute("data-caption") != null)
      slide += `<div class="k-box-caption">${elementsList[i].getAttribute(
        "data-caption"
      )}</div>`;

    slide += `</div>`;
    imgs.push(slide);
  }

  return imgs;
}

async function loadThumbnails(elementsList) {
  let thumbs = [];
  let image = "";

  for (let i = 0; i < elementsList.length; i++) {
    if (
      elementsList[i].getAttribute("data-thumb") != null &&
      elementsList[i].getAttribute("data-thumb") != ""
    )
      image = await checkUrl(
        elementsList[i].getAttribute("data-thumb"),
        false,
        true,
        true,
        i
      );
    else
      image = await checkUrl(
        elementsList[i].getAttribute("href"),
        false,
        true,
        false,
        i
      );
    image += `</div>`;
    thumbs.push(image);
  }

  return thumbs;
}

function changeKBoxSlide(slides, btn) {
  const counter = document.querySelector(
    "#kBoxContainer .current-slider-counter"
  );
  const prev = document.querySelector("#kBoxContainer .box-arrow.arrow-left");
  const next = document.querySelector("#kBoxContainer .box-arrow.arrow-right");
  const thumbnails = document.querySelectorAll(
    "#kBoxContainer .thumbnail-slide"
  );
  const thumbnailInner = document.querySelector(
    "#kBoxContainer .box-thumbnails-inner"
  );

  if (btn.classList.contains("disabled")) return;

  let currentSlideIndex = 0;
  for (let i = 0; i < slides.length; i++) {
    if (slides[i].classList.contains("current-slide")) {
      currentSlideIndex = i;
      slides[i].classList.remove("current-slide");
      break;
    }
  }

  // Remove Youtube/Vimeo Iframe
  if (
    slides[currentSlideIndex].classList.contains("yt--slide") ||
    slides[currentSlideIndex].classList.contains("vimeo--slide")
  ) {
    for (
      let i = 0;
      i < slides[currentSlideIndex].childNodes[1].childNodes.length;
      i++
    )
      slides[currentSlideIndex].childNodes[1].childNodes[i].remove();
  }
  // Pause Video
  else if (slides[currentSlideIndex].classList.contains("video--slide")) {
    slides[currentSlideIndex].childNodes[1].childNodes[1].pause();
  }

  if (btn.classList.contains("arrow-left")) {
    if (next.classList.contains("disabled")) next.classList.remove("disabled");

    if (currentSlideIndex == 1) btn.classList.add("disabled");

    currentSlideIndex--;
  } else if (btn.classList.contains("arrow-right")) {
    if (prev.classList.contains("disabled")) prev.classList.remove("disabled");

    if (currentSlideIndex == slides.length - 2) btn.classList.add("disabled");

    currentSlideIndex++;
  }

  // Create Youtube Iframe in autoplay
  if (slides[currentSlideIndex].classList.contains("yt--slide")) {
    let videoCode =
      slides[currentSlideIndex].childNodes[1].getAttribute("data-code");
    let iframe = `<iframe 
                    src="https://www.youtube.com/embed/${videoCode}?autoplay=1&autohide=1&wmode=transparent&enablejsapi=1&html5=1"
                    allow="autoplay; fullscreen"
                    scrolling="no"
                  ></iframe>`;
    slides[currentSlideIndex].childNodes[1].insertAdjacentHTML(
      "beforeend",
      iframe
    );
  }
  // Create Vimeo Iframe in autoplay
  else if (slides[currentSlideIndex].classList.contains("vimeo--slide")) {
    let videoCode =
      slides[currentSlideIndex].childNodes[1].getAttribute("data-code");
    let iframe = `<iframe 
                    src="https://player.vimeo.com/video/${videoCode}?byline=1&color=00adef&controls=1&dnt=1&muted=0&autoplay=1&autopause=0"
                    allow="autoplay; fullscreen"
                    scrolling="no"
                  ></iframe>
                  <script src="https://player.vimeo.com/api/player.js"></script>`;
    slides[currentSlideIndex].childNodes[1].insertAdjacentHTML(
      "beforeend",
      iframe
    );
  }
  // Play video
  else if (slides[currentSlideIndex].classList.contains("video--slide")) {
    slides[currentSlideIndex].childNodes[1].childNodes[1].play();
  }

  slides[currentSlideIndex].classList.add("current-slide");
  counter.innerHTML = currentSlideIndex + 1;

  // Change current thumbnail
  if (thumbnails.length > 0) {
    for (let t = 0; t < thumbnails.length; t++) {
      if (t == currentSlideIndex) {
        thumbnails[t].classList.add("current-thumbnail");
        thumbnailInner.style.transform =
          "matrix(1,0,0,1," +
          (window.innerWidth / 2 -
            Math.abs(thumbnails[t].offsetLeft) -
            thumbnails[t].offsetWidth / 2 -
            15) +
          ", 0)";
      } else thumbnails[t].classList.remove("current-thumbnail");
    }
  }
}

function checkUrl(
  href,
  lazyload,
  isThumb = false,
  customThumb = false,
  slideIndex = null
) {
  let template = "";

  return new Promise((resolve, reject) => {
    const types = new Map([
      ["jpg", "img"],
      ["gif", "img"],
      ["mp4", "video"],
      ["3gp", "video"],
    ]);

    if (href.charAt(0) == "/") href = window.location.origin + href;

    const url = new URL(href);
    const extension = url.pathname.split(".")[1];

    if (types.get(extension) == "video") {
      if (isThumb) {
        if (customThumb)
          template = `<div class="thumbnail-slide" data-index="${slideIndex}">
                        <img src="${href}" class="thumb-image" />`;
        else
          template = `<div class="thumbnail-slide" data-index="${slideIndex}">
                        <div class="thumb-image video-placeholder"></div>`;
      } else
        template = `<div class="box-slide video--slide">
                      <div class="iframe-container">
                        <video controls playsinline>
                          <source src="${href}" type="video/${extension}" />
                        </video>
                      </div>`;
      resolve(template);
    } else {
      var image = new Image();
      image.onload = function () {
        if (this.width > 0) {
          if (isThumb)
            template = `<div class="thumbnail-slide" data-index="${slideIndex}">
                          <img src="${href}" class="thumb-image" />`;
          else
            template = `<div class="box-slide image--slide">
						  			      <img src="${href}" class="k-box-image" />`;
          resolve(template);
        }
      };
      image.onerror = function () {
        if (
          href
            .replace("http://", "")
            .replace("https://", "")
            .replace("www.", "")
            .replace("youtu.be/", "youtube.com/watch?v=")
            .slice(0, 20) === "youtube.com/watch?v="
        ) {
          let videoCode = url.search.replace("?v=", "");
          if (isThumb) {
            if (customThumb)
              template = `<div class="thumbnail-slide" data-index="${slideIndex}">
                            <img src="${href}" class="thumb-image" />`;
            else
              template = `<div class="thumbnail-slide" data-index="${slideIndex}">
                            <img src="https://i3.ytimg.com/vi/${videoCode}/hqdefault.jpg" class="thumb-image" />`;
          } else {
            if (!lazyload)
              template = `<div class="box-slide yt--slide">
                            <div class="iframe-container" data-code="${videoCode}"><iframe 
                                src="https://www.youtube.com/embed/${videoCode}?autoplay=1&autohide=1&wmode=transparent&enablejsapi=1&html5=1"
                                allow="autoplay; fullscreen"
                                scrolling="no"
                              ></iframe></div>`;
            else
              template = `<div class="box-slide yt--slide">
                            <div class="iframe-container" data-code="${videoCode}"></div>`;
          }
        } else if (
          href
            .replace("http://", "")
            .replace("https://", "")
            .replace("www.", "")
            .slice(0, 9) == "vimeo.com"
        ) {
          let videoCode = url.pathname.replace("/", "");
          if (isThumb) {
            if (customThumb)
              template = `<div class="thumbnail-slide" data-index="${slideIndex}">
                            <img src="${href}" class="thumb-image" />`;
            else
              template = `<div class="thumbnail-slide" data-index="${slideIndex}">
                            <div class="thumb-image video-placeholder"></div>`;
          } else {
            if (!lazyload)
              template = `<div class="box-slide vimeo--slide">
                            <div class="iframe-container" data-code="${videoCode}">
                              <iframe 
                                src="https://player.vimeo.com/video/${videoCode}?byline=1&color=00adef&controls=1&dnt=1&muted=0&autoplay=1&autopause=0"
                                allow="autoplay; fullscreen"
                                scrolling="no"
                              ></iframe>
                              <script src="https://player.vimeo.com/api/player.js"></script>
                            </div>`;
            else
              template = `<div class="box-slide vimeo--slide">
                            <div class="iframe-container" data-code="${videoCode}"></div>`;
          }
        } else {
          // console.error('K-box ERROR: image not found or not supported');
          if (isThumb)
            template = `<div class="thumbnail-slide" data-index="${slideIndex}">
                          <div class="thumb-image error-placeholder"></div>`;
          else
            template = `<div class="box-slide error--slide">
                          <div class="error-box">Image not found</div>`;
        }
        resolve(template);
      };
      image.src = href;
    }
  });
}

// Get current K-Box index
function getCurrentIndex(slides) {
  let currentIndex = 0;
  for (let i = 0; i < slides.length; i++) {
    if (slides[i].classList.contains("current-slide")) currentIndex = i;
  }
  
  return currentIndex;
}

// Change slides by given index
function changeKBoxIndex(slides, newIndex = 0) {
  const counter = document.querySelector(
    "#kBoxContainer .current-slider-counter"
  );
  const prev = document.querySelector("#kBoxContainer .box-arrow.arrow-left");
  const next = document.querySelector("#kBoxContainer .box-arrow.arrow-right");
  const thumbnails = document.querySelectorAll(
    "#kBoxContainer .thumbnail-slide"
  );
  const thumbnailInner = document.querySelector(
    "#kBoxContainer .box-thumbnails-inner"
  );

  if (newIndex >= 0 && newIndex <= slides.length - 1) {
    let currentIndex = getCurrentIndex(slides);
    for (let i = 0; i < slides.length; i++) {
      if (i == newIndex) slides[i].classList.add("current-slide");
      else if (slides[i].classList.contains("current-slide"))
        slides[i].classList.remove("current-slide");
    }

    // Remove Youtube Iframe previous slide
    if (slides[currentIndex].classList.contains("yt--slide")) {
      for (
        let i = 0;
        i < slides[currentIndex].childNodes[1].childNodes.length;
        i++
      )
        slides[currentIndex].childNodes[1].childNodes[i].remove();
    }
    // Pause Video previous slide
    else if (slides[currentIndex].classList.contains("video--slide")) {
      slides[currentIndex].childNodes[1].childNodes[1].pause();
    }

    // Create Youtube Iframe in autoplay new slide
    if (slides[newIndex].classList.contains("yt--slide")) {
      let videoCode = slides[newIndex].childNodes[1].getAttribute("data-code");
      let iframe = `<iframe 
                      src="https://www.youtube.com/embed/${videoCode}?autoplay=1&autohide=1&wmode=transparent&enablejsapi=1&html5=1"
                      allow="autoplay; fullscreen"
                      scrolling="no"
                    ></iframe>`;
      slides[newIndex].childNodes[1].insertAdjacentHTML("beforeend", iframe);
    }
    // Create Vimeo Iframe in autoplay
    else if (slides[newIndex].classList.contains("vimeo--slide")) {
      let videoCode = slides[newIndex].childNodes[1].getAttribute("data-code");
      let iframe = `<iframe 
                      src="https://player.vimeo.com/video/${videoCode}?byline=1&color=00adef&controls=1&dnt=1&muted=0&autoplay=1&autopause=0"
                      allow="autoplay; fullscreen"
                      scrolling="no"
                    ></iframe>
                    <script src="https://player.vimeo.com/api/player.js"></script>`;
      slides[newIndex].childNodes[1].insertAdjacentHTML("beforeend", iframe);
    }
    // Play video new slide
    else if (slides[newIndex].classList.contains("video--slide")) {
      slides[newIndex].childNodes[1].childNodes[1].play();
    }

    if (slides.length > 1) {
      // Set counter
      counter.innerHTML = Number(newIndex) + 1;

      // Disabled buttons if first or last slide
      if (newIndex == 0) prev.classList.add("disabled");
      else prev.classList.remove("disabled");

      if (newIndex == slides.length - 1) next.classList.add("disabled");
      else next.classList.remove("disabled");

      // Change current thumbnail
      if (thumbnails.length > 0) {
        for (let t = 0; t < thumbnails.length; t++) {
          if (t == newIndex) {
            thumbnails[t].classList.add("current-thumbnail");
            thumbnailInner.style.transform =
              "matrix(1,0,0,1," +
              (window.innerWidth / 2 -
                Math.abs(thumbnails[t].offsetLeft) -
                thumbnails[t].offsetWidth / 2 -
                15) +
              ", 0)";
          } else thumbnails[t].classList.remove("current-thumbnail");
        }
      }
    }
  }
}
