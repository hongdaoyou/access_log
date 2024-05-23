# 获取,access的数据

BEGIN {
}

{
# 截取,得到,用户代理
split($0, arr, "\"");
userAgent=arr[6];

# 具体的数据
ip= $1

access_time= substr($4,2)
method = substr($6,2)
uri = $7
httpProtocol = substr($8,1, length($8)-1)
responeCode = $9
dataLen = $10

# refer
refer = substr($11, 2, length($11)-2)
# printf("AAA:"%s"\n", refer);

if (refer == "-") {
    refer = "";
}


# 不含有""
# printf("ip:%s\n",ip);
# printf("access_time:%s\n",access_time);
# printf("method:%s\n",method);
# printf("uri:%s\n",uri);
# printf("httpProtocol:%s\n",httpProtocol);
# printf("responeCode:%s\n",responeCode);
# printf("dataLen:%s\n",dataLen);
# printf("refer:%s\n",refer);
# printf("userAgent:%s\n",userAgent);



print("{");

printf("\"ip\":\"%s\",",ip);

printf("\"access_time\":\"%s\",",access_time);
printf("\"method\":\"%s\",",method);
printf("\"uri\":\"%s\",",uri);
printf("\"httpProtocol\":\"%s\",",httpProtocol);
printf("\"responeCode\":\"%s\",",responeCode);
printf("\"dataLen\":\"%s\",",dataLen);
printf("\"refer\":\"%s\",",refer);
printf("\"userAgent\":\"%s\"",userAgent);

print("}");

printf("______________\n\n");

}

END {

}
