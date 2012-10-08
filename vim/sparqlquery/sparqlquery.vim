command -nargs=* Sparql call Sparql(<f-args>)

function! Sparql(...)
    " if called with: :Sparql <endpoint>, store endpoint globally
    if a:0 == 1
        let g:sparql_endpoint = a:1
    elseif !exists('g:sparql_endpoint')
        echoerr 'No SPARQL endpoint set, use :Sparql <endpoint> first'
        return
    endif

    " copy contents of main window buffer, replace newlines with spaces
    1wincmd w
    silent %y
    let l:query = substitute(@0, '\n', ' ', 'g')

    let window_number = bufwinnr('Results')
    if (window_number == -1)
        " if 2nd window isn't open, open one, name it 'Results'
        belowright new
        setlocal buftype=nofile
        setlocal bufhidden=delete
        setlocal noswapfile
        file Results
    else
        " else change to it if it already exists
        exec window_number . 'wincmd w'
    endif

    " build command line string and execute it
    let l:vars = shellescape('output=text&query=' . query)
    let l:cmd = 'curl -s -d ' . l:vars . ' ' .g:sparql_endpoint
    let l:result = system(l:cmd)

    " delete contents of result buffer, paste results in
    silent %d
    let @@ = l:result
    normal! P

    " move cursor back to first window
    wincmd w
endfunction
