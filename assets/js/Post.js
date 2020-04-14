'use strict'
let pageLock          = null
window.onbeforeunload = isPageLocked

/**
 * Return value of pageLock
 *
 * @returns {null|boolean}
 */
function isPageLocked () {
  if (pageLock) return pageLock

  return null
}

/**
 *
 * @param status
 * @param messageSelector
 */
function lockPage (status) {
  pageLock = status
  // const element = document.querySelector(messageSelector)
  // if (element) element.innerHTML = status ? '☒︎' : ''
}

/**
 * Creating a new content block
 *
 * @param event
 */
function createNewBlock (event) {
  event.preventDefault()
  // search for root block element
  const parent = event.target.closest('.js__wp-block')
  const select = parent.querySelector('.js__block-type')
  const offset = parseFloat(getComputedStyle(select).height)
  // add new block after selected block
  addBlock({
    templateId: 'template__wp-block',
    element: parent,
    focusOn: '.js__block-content',
    scrollOffset: -offset,
  })
  // reindex all blocks
  indexDynamicFields('.js__wp-block')
}

// reindex all blocks
indexDynamicFields('.js__wp-block')

on('change', '#myTheme-Post [value]', () => lockPage(true))

// Listen for click on a plus button
on('click', '.js__add-wp-block', (event) => {
  createNewBlock(event)
})

// Listen for click on a plus button
on('keydown keyup', '.js__block-content', (event) => {
  resizeTextarea(event.target)
  if (13 === event.keyCode) {
    createNewBlock(event)
  }
})

// Listen for click on a plus button
on('change', '.js__block-type', (event) => {
  event.preventDefault()
  const select = event.target
  // search for root block element
  const parent = select.closest('.js__wp-block')
  if ('remove' === select.value) {
    // remove selected block
    parent.remove()
    // reindex all blocks
    indexDynamicFields('.js__wp-block')
  } else {
    parent.querySelector('.js__block-options').value = select.options[select.selectedIndex].getAttribute('data-options')
  }
})

on('focus click', '.js__block-content', (event) => {
  const blockContent = event.target
  const block        = blockContent.closest('.js__wp-block')
  const blockType    = block.querySelector('.js__block-type')
  const add          = block.querySelector('.js__add')

  document.querySelectorAll('.js__block-type').forEach((element, index) => {
    element.toggleClass('active', false)
  })
  document.querySelectorAll('.js__block-content').forEach((element, index) => {
    element.toggleClass('active', false)
  })
  document.querySelectorAll('.js__add').forEach((element, index) => {
    element.toggleClass('active', false)
  })
  blockContent.toggleClass('active', false)

  blockType.toggleClass('active', true)
  add.toggleClass('active', true)
  blockContent.toggleClass('active', true)
})

